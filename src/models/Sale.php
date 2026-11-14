<?php

declare(strict_types=1);

namespace App\Models;

use Database;
use PDO;
use RuntimeException;

final class Sale
{
  public function __construct(private readonly Database $database) {}

  /**
   * Confirma una venta COMPLETA en una sola transacción.
   *
   * El servidor recalcula todo desde la base (precios y stock); del cliente solo
   * llegan ids, cantidades, tipo/valor del descuento, método y monto recibido.
   *
   * @param list<array{product_id: int, quantity: int}> $items
   * @param 'none'|'percent'|'amount' $discountType
   * @param int $discountParam  centésimas de % si es 'percent'; centavos si es 'amount'; se ignora si es 'none'
   * @return array{status: 'ok', sale_id: int}|array{status: 'error', errors: list<string>}
   */
  public function create(
    int $repositoryId,
    int $userId,
    array $items,
    string $discountType,
    int $discountParam,
    string $paymentMethod,
    ?int $receivedCents,
  ): array {
    $result = $this->database->runTransaction(
      static function (PDO $connection) use (
        $repositoryId,
        $userId,
        $items,
        $discountType,
        $discountParam,
        $paymentMethod,
        $receivedCents,
      ): array {
        // 1. Turno abierto de ESTE repositorio. Bloqueo compartido: varias ventas pueden
        //    convivir, pero el cierre del turno (FOR UPDATE) espera a que terminen.
        $stmt = $connection->prepare(
          "SELECT id FROM cash_sessions
           WHERE repository_id = :repository_id AND status = 'open'
           ORDER BY id DESC LIMIT 1
           LOCK IN SHARE MODE",
        );
        $stmt->execute(["repository_id" => $repositoryId]);
        $sessionId = $stmt->fetchColumn();

        if ($sessionId === false) {
          return [
            "status" => "error",
            "errors" => ["No hay un turno de caja abierto. Abre la caja para vender."],
          ];
        }

        // 2. Bloquea los productos (ordenados por id para evitar deadlocks).
        //    Solo activos y SIEMPRE del repositorio de la URL.
        $ids = array_column($items, "product_id");
        sort($ids);
        $marks = implode(",", array_fill(0, count($ids), "?"));

        $stmt = $connection->prepare(
          "SELECT id, code, name, price, stock
           FROM products
           WHERE repository_id = ? AND active = 1 AND id IN ($marks)
           ORDER BY id ASC
           FOR UPDATE",
        );
        $stmt->execute([$repositoryId, ...$ids]);

        $products = [];

        foreach ($stmt->fetchAll() as $row) {
          $products[(int) $row["id"]] = $row;
        }

        if (count($products) !== count($ids)) {
          return [
            "status" => "error",
            "errors" => ["Uno o más productos ya no están disponibles en este repositorio. Revisa la venta."],
          ];
        }

        // 3. Calcula todo en centavos enteros
        $errors = [];
        $lines = [];
        $subtotal = 0;

        foreach ($items as $item) {
          $product = $products[$item["product_id"]];
          $quantity = $item["quantity"];
          $stock = (int) $product["stock"];

          if ($quantity > $stock) {
            $errors[] = sprintf(
              "Stock insuficiente de «%s»: disponible %d, pedido %d.",
              (string) $product["name"],
              $stock,
              $quantity,
            );
            continue;
          }

          $unitCents = Money::fromDecimal((string) $product["price"]);
          $lineCents = $unitCents * $quantity;
          $subtotal += $lineCents;

          $lines[] = [
            "product_id" => $item["product_id"],
            "quantity" => $quantity,
            "unit_cents" => $unitCents,
            "line_cents" => $lineCents,
          ];
        }

        if ($errors !== []) {
          return ["status" => "error", "errors" => $errors];
        }

        if ($subtotal > Money::MAX_TOTAL_CENTS) {
          return [
            "status" => "error",
            "errors" => ["El subtotal de la venta supera el máximo permitido (" . Money::formatCents(Money::MAX_TOTAL_CENTS) . ")."],
          ];
        }

        $discountCents = 0;
        $discountValue = "0.00";

        if ($discountType === "percent") {
          $discountCents = Money::percentOf($subtotal, $discountParam);
          $discountValue = Money::toDecimal($discountParam);
        } elseif ($discountType === "amount") {
          if ($discountParam > $subtotal) {
            return [
              "status" => "error",
              "errors" => ["El descuento no puede ser mayor al subtotal (" . Money::formatCents($subtotal) . ")."],
            ];
          }

          $discountCents = $discountParam;
          $discountValue = Money::toDecimal($discountParam);
        }

        $total = $subtotal - $discountCents;
        $received = null;
        $change = null;

        if ($paymentMethod === "efectivo") {
          if ($receivedCents === null || $receivedCents < $total) {
            return [
              "status" => "error",
              "errors" => ["El monto recibido no alcanza para cubrir el total (" . Money::formatCents($total) . ")."],
            ];
          }

          $received = $receivedCents;
          $change = $receivedCents - $total;
        }

        // 4. Cabecera de la venta
        $stmt = $connection->prepare(
          "INSERT INTO sales
             (repository_id, user_id, cash_session_id, subtotal, discount_type, discount_value,
              discount_amount, total, payment_method, amount_received, change_given)
           VALUES
             (:repository_id, :user_id, :cash_session_id, :subtotal, :discount_type, :discount_value,
              :discount_amount, :total, :payment_method, :amount_received, :change_given)",
        );
        $stmt->execute([
          "repository_id" => $repositoryId,
          "user_id" => $userId,
          "cash_session_id" => (int) $sessionId,
          "subtotal" => Money::toDecimal($subtotal),
          "discount_type" => $discountType,
          "discount_value" => $discountValue,
          "discount_amount" => Money::toDecimal($discountCents),
          "total" => Money::toDecimal($total),
          "payment_method" => $paymentMethod,
          "amount_received" => $received === null ? null : Money::toDecimal($received),
          "change_given" => $change === null ? null : Money::toDecimal($change),
        ]);

        $saleId = (int) $connection->lastInsertId();

        // 5. Renglones, descuento de stock y movimientos 'venta' (cantidad NEGATIVA)
        $insertItem = $connection->prepare(
          "INSERT INTO sale_items (sale_id, product_id, quantity, unit_price, subtotal)
           VALUES (:sale_id, :product_id, :quantity, :unit_price, :subtotal)",
        );
        $updateStock = $connection->prepare(
          "UPDATE products SET stock = stock - :quantity
           WHERE id = :id AND repository_id = :repository_id AND stock >= :needed",
        );
        $insertMovement = $connection->prepare(
          "INSERT INTO stock_movements (product_id, user_id, sale_id, type, quantity, note)
           VALUES (:product_id, :user_id, :sale_id, 'venta', :quantity, :note)",
        );

        foreach ($lines as $line) {
          $insertItem->execute([
            "sale_id" => $saleId,
            "product_id" => $line["product_id"],
            "quantity" => $line["quantity"],
            "unit_price" => Money::toDecimal($line["unit_cents"]),
            "subtotal" => Money::toDecimal($line["line_cents"]),
          ]);

          $updateStock->execute([
            "quantity" => $line["quantity"],
            "id" => $line["product_id"],
            "repository_id" => $repositoryId,
            "needed" => $line["quantity"],
          ]);

          // Red de seguridad: el producto ya estaba bloqueado, esto no debería pasar nunca
          if ($updateStock->rowCount() !== 1) {
            throw new RuntimeException("No se pudo descontar el stock del producto " . $line["product_id"] . ".");
          }

          $insertMovement->execute([
            "product_id" => $line["product_id"],
            "user_id" => $userId,
            "sale_id" => $saleId,
            "quantity" => -$line["quantity"],
            "note" => "Venta N.º " . $saleId,
          ]);
        }

        return ["status" => "ok", "sale_id" => $saleId];
      },
    );

    return (array) $result;
  }

  /**
   * Datos para el panel de confirmación, solo si la venta es de ese repositorio.
   *
   * @return array{id: int, total_cents: int, payment_method: string, received_cents: int|null, change_cents: int|null}|null
   */
  public function findForReceipt(int $saleId, int $repositoryId): ?array
  {
    $stmt = $this->database->getConnection()->prepare(
      "SELECT id, total, payment_method, amount_received, change_given
       FROM sales
       WHERE id = :id AND repository_id = :repository_id
       LIMIT 1",
    );
    $stmt->execute(["id" => $saleId, "repository_id" => $repositoryId]);

    $row = $stmt->fetch();

    if ($row === false) {
      return null;
    }

    return [
      "id" => (int) $row["id"],
      "total_cents" => Money::fromDecimal((string) $row["total"]),
      "payment_method" => (string) $row["payment_method"],
      "received_cents" => $row["amount_received"] === null ? null : Money::fromDecimal((string) $row["amount_received"]),
      "change_cents" => $row["change_given"] === null ? null : Money::fromDecimal((string) $row["change_given"]),
    ];
  }

  /**
   * Cantidad de ventas del historial (con los mismos filtros que listForHistory).
   * $onlyUserId null = todas (admin); un id = solo las de ese usuario (miembro).
   */
  public function countForHistory(
    int $repositoryId,
    ?int $onlyUserId,
    ?string $from,
    ?string $toExclusive,
  ): int {
    [$where, $params] = self::historyFilter($repositoryId, $onlyUserId, $from, $toExclusive);

    $stmt = $this->database->getConnection()->prepare(
      "SELECT COUNT(*) FROM sales s WHERE " . $where,
    );
    $stmt->execute($params);

    return (int) $stmt->fetchColumn();
  }

  /**
   * Una página del historial, de la más reciente a la más antigua.
   *
   * @return list<array<string, mixed>>
   */
  public function listForHistory(
    int $repositoryId,
    ?int $onlyUserId,
    ?string $from,
    ?string $toExclusive,
    int $limit,
    int $offset,
  ): array {
    [$where, $params] = self::historyFilter($repositoryId, $onlyUserId, $from, $toExclusive);

    $stmt = $this->database->getConnection()->prepare(
      "SELECT s.id, s.created_at, s.cash_session_id, s.payment_method, s.discount_type,
              s.discount_value, s.discount_amount, s.subtotal, s.total, u.name AS user_name
       FROM sales s
       INNER JOIN users u ON u.id = s.user_id
       WHERE " . $where . "
       ORDER BY s.created_at DESC, s.id DESC
       LIMIT " . max(1, $limit) . " OFFSET " . max(0, $offset),
    );
    $stmt->execute($params);

    return array_map(
      static fn(array $row): array => [
        "id" => (int) $row["id"],
        "created_at" => (string) $row["created_at"],
        "user_name" => (string) $row["user_name"],
        "session_id" => $row["cash_session_id"] === null ? null : (int) $row["cash_session_id"],
        "payment_method" => (string) $row["payment_method"],
        "discount_type" => (string) $row["discount_type"],
        "discount_value" => (string) $row["discount_value"],
        "discount_cents" => Money::fromDecimal((string) $row["discount_amount"]),
        "subtotal_cents" => Money::fromDecimal((string) $row["subtotal"]),
        "total_cents" => Money::fromDecimal((string) $row["total"]),
      ],
      $stmt->fetchAll(),
    );
  }

  /**
   * Los ítems de varias ventas en UNA sola consulta (sin N+1), agrupados por venta.
   *
   * @param list<int> $saleIds
   * @return array<int, list<array{code: int, name: string, quantity: int, unit_cents: int, subtotal_cents: int}>>
   */
  public function itemsForSales(array $saleIds): array
  {
    if ($saleIds === []) {
      return [];
    }

    $marks = implode(",", array_fill(0, count($saleIds), "?"));

    $stmt = $this->database->getConnection()->prepare(
      "SELECT si.sale_id, p.code, p.name, si.quantity, si.unit_price, si.subtotal
       FROM sale_items si
       INNER JOIN products p ON p.id = si.product_id
       WHERE si.sale_id IN ($marks)
       ORDER BY si.sale_id ASC, si.id ASC",
    );
    $stmt->execute(array_values($saleIds));

    $grouped = [];

    foreach ($stmt->fetchAll() as $row) {
      $grouped[(int) $row["sale_id"]][] = [
        "code" => (int) $row["code"],
        "name" => (string) $row["name"],
        "quantity" => (int) $row["quantity"],
        "unit_cents" => Money::fromDecimal((string) $row["unit_price"]),
        "subtotal_cents" => Money::fromDecimal((string) $row["subtotal"]),
      ];
    }

    return $grouped;
  }

  /**
   * @return array{0: string, 1: array<string, mixed>}
   */
  private static function historyFilter(
    int $repositoryId,
    ?int $onlyUserId,
    ?string $from,
    ?string $toExclusive,
  ): array {
    $where = "s.repository_id = :repository_id";
    $params = ["repository_id" => $repositoryId];

    if ($onlyUserId !== null) {
      $where .= " AND s.user_id = :user_id";
      $params["user_id"] = $onlyUserId;
    }

    if ($from !== null) {
      $where .= " AND s.created_at >= :date_from";
      $params["date_from"] = $from;
    }

    if ($toExclusive !== null) {
      $where .= " AND s.created_at < :date_to";
      $params["date_to"] = $toExclusive;
    }

    return [$where, $params];
  }
}
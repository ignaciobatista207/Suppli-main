<?php

declare(strict_types=1);

namespace App\Models;

use Database;
use PDO;

final class CashSession
{
  private const SELECT = "SELECT s.id, s.status, s.opened_by, s.opened_at, s.closed_at,
                                 s.opening_amount, s.expected_cash, s.counted_cash, s.difference, s.close_note,
                                 ou.name AS opened_by_name, cu.name AS closed_by_name
                          FROM cash_sessions s
                          INNER JOIN users ou ON ou.id = s.opened_by
                          LEFT JOIN users cu ON cu.id = s.closed_by";

  public function __construct(private readonly Database $database) {}

  /**
   * El turno ABIERTO del repositorio (como máximo hay uno) o null.
   *
   * @return array<string, mixed>|null
   */
  public function findOpen(int $repositoryId): ?array
  {
    $stmt = $this->database->getConnection()->prepare(
      self::SELECT . " WHERE s.repository_id = :repository_id AND s.status = 'open'
                       ORDER BY s.id DESC LIMIT 1",
    );
    $stmt->execute(["repository_id" => $repositoryId]);

    $row = $stmt->fetch();

    return $row === false ? null : self::map($row);
  }

  /**
   * Un turno CERRADO, solo si es de ese repositorio.
   *
   * @return array<string, mixed>|null
   */
  public function findClosed(int $sessionId, int $repositoryId): ?array
  {
    $stmt = $this->database->getConnection()->prepare(
      self::SELECT . " WHERE s.id = :id AND s.repository_id = :repository_id AND s.status = 'closed'
                       LIMIT 1",
    );
    $stmt->execute(["id" => $sessionId, "repository_id" => $repositoryId]);

    $row = $stmt->fetch();

    return $row === false ? null : self::map($row);
  }

  /**
   * Últimos turnos del repositorio (más reciente primero).
   *
   * @return list<array<string, mixed>>
   */
  public function listRecent(int $repositoryId, int $limit = 10): array
  {
    $limit = max(1, min(50, $limit));

    $stmt = $this->database->getConnection()->prepare(
      self::SELECT . " WHERE s.repository_id = :repository_id ORDER BY s.id DESC LIMIT " . $limit,
    );
    $stmt->execute(["repository_id" => $repositoryId]);

    return array_map(static fn(array $row): array => self::map($row), $stmt->fetchAll());
  }

  /**
   * Abre un turno. En una transacción bloquea la fila del repositorio (así dos
   * aperturas simultáneas se ponen en fila) y verifica que no haya otro abierto.
   *
   * @return 'opened'|'already_open'|'missing'
   */
  public function open(int $repositoryId, int $userId, int $openingCents): string
  {
    $result = $this->database->runTransaction(
      static function (PDO $connection) use ($repositoryId, $userId, $openingCents): string {
        $stmt = $connection->prepare("SELECT id FROM repositories WHERE id = :id FOR UPDATE");
        $stmt->execute(["id" => $repositoryId]);

        if ($stmt->fetch() === false) {
          return "missing";
        }

        $stmt = $connection->prepare(
          "SELECT 1 FROM cash_sessions
           WHERE repository_id = :repository_id AND status = 'open' LIMIT 1",
        );
        $stmt->execute(["repository_id" => $repositoryId]);

        if ($stmt->fetchColumn() !== false) {
          return "already_open";
        }

        $stmt = $connection->prepare(
          "INSERT INTO cash_sessions (repository_id, opened_by, opening_amount)
           VALUES (:repository_id, :opened_by, :opening_amount)",
        );
        $stmt->execute([
          "repository_id" => $repositoryId,
          "opened_by" => $userId,
          "opening_amount" => Money::toDecimal($openingCents),
        ]);

        return "opened";
      },
    );

    return (string) $result;
  }

  /**
   * Resumen de ventas de un turno: cantidad y total (en centavos) por método de pago.
   *
   * @return array{count: int, total_cents: int, by_method: array<string, array{count: int, total_cents: int}>}
   */
  public function summary(int $sessionId): array
  {
    $stmt = $this->database->getConnection()->prepare(
      "SELECT payment_method, COUNT(*) AS n, COALESCE(SUM(total), 0) AS total
       FROM sales
       WHERE cash_session_id = :cash_session_id
       GROUP BY payment_method",
    );
    $stmt->execute(["cash_session_id" => $sessionId]);

    $byMethod = [
      "efectivo" => ["count" => 0, "total_cents" => 0],
      "transferencia" => ["count" => 0, "total_cents" => 0],
      "tarjeta" => ["count" => 0, "total_cents" => 0],
    ];
    $count = 0;
    $total = 0;

    foreach ($stmt->fetchAll() as $row) {
      $method = (string) $row["payment_method"];
      $n = (int) $row["n"];
      $cents = Money::fromDecimal((string) $row["total"]);

      if (isset($byMethod[$method])) {
        $byMethod[$method] = ["count" => $n, "total_cents" => $cents];
      }

      $count += $n;
      $total += $cents;
    }

    return ["count" => $count, "total_cents" => $total, "by_method" => $byMethod];
  }

  /**
   * Cierra el turno. En una transacción: bloquea la fila del turno, calcula el
   * efectivo esperado (apertura + ventas en efectivo) y guarda el resultado.
   * Las ventas toman un bloqueo compartido sobre esa misma fila, así que una
   * venta en curso termina antes de que el cierre calcule los totales.
   *
   * @return array{status: 'closed'|'not_open', expected_cents: int, difference_cents: int}
   */
  public function close(
    int $repositoryId,
    int $sessionId,
    int $userId,
    int $countedCents,
    ?string $note,
  ): array {
    $result = $this->database->runTransaction(
      static function (PDO $connection) use ($repositoryId, $sessionId, $userId, $countedCents, $note): array {
        $stmt = $connection->prepare(
          "SELECT opening_amount FROM cash_sessions
           WHERE id = :id AND repository_id = :repository_id AND status = 'open'
           FOR UPDATE",
        );
        $stmt->execute(["id" => $sessionId, "repository_id" => $repositoryId]);

        $row = $stmt->fetch();

        if ($row === false) {
          return ["status" => "not_open", "expected_cents" => 0, "difference_cents" => 0];
        }

        $stmt = $connection->prepare(
          "SELECT COALESCE(SUM(total), 0) FROM sales
           WHERE cash_session_id = :cash_session_id AND payment_method = 'efectivo'",
        );
        $stmt->execute(["cash_session_id" => $sessionId]);

        $expected = Money::fromDecimal((string) $row["opening_amount"])
          + Money::fromDecimal((string) $stmt->fetchColumn());
        $difference = $countedCents - $expected;

        $stmt = $connection->prepare(
          "UPDATE cash_sessions
           SET status = 'closed', closed_by = :closed_by, closed_at = NOW(),
               expected_cash = :expected_cash, counted_cash = :counted_cash,
               difference = :difference, close_note = :close_note
           WHERE id = :id",
        );
        $stmt->execute([
          "closed_by" => $userId,
          "expected_cash" => Money::toDecimal($expected),
          "counted_cash" => Money::toDecimal($countedCents),
          "difference" => Money::toDecimal($difference),
          "close_note" => $note,
          "id" => $sessionId,
        ]);

        return ["status" => "closed", "expected_cents" => $expected, "difference_cents" => $difference];
      },
    );

    return (array) $result;
  }

  /**
   * @param array<string, mixed> $row
   * @return array<string, mixed>
   */
  private static function map(array $row): array
  {
    $nullableCents = static fn(mixed $value): ?int => $value === null
      ? null
      : Money::fromDecimal((string) $value);

    return [
      "id" => (int) $row["id"],
      "status" => (string) $row["status"],
      "opened_by" => (int) $row["opened_by"],
      "opened_by_name" => (string) $row["opened_by_name"],
      "closed_by_name" => $row["closed_by_name"] === null ? null : (string) $row["closed_by_name"],
      "opened_at" => (string) $row["opened_at"],
      "closed_at" => $row["closed_at"] === null ? null : (string) $row["closed_at"],
      "opening_cents" => Money::fromDecimal((string) $row["opening_amount"]),
      "expected_cents" => $nullableCents($row["expected_cash"]),
      "counted_cents" => $nullableCents($row["counted_cash"]),
      "difference_cents" => $nullableCents($row["difference"]),
      "close_note" => $row["close_note"] === null ? null : (string) $row["close_note"],
    ];
  }
}
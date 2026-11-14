<?php

declare(strict_types=1);

namespace App\Models;

use Database;
use PDO;

final class Product
{
  public function __construct(private readonly Database $database) {}

  /**
   * Productos activos de una góndola, por nombre.
   * is_low = true cuando min_stock > 0 y stock <= min_stock.
   *
   * @return list<array{id: int, name: string, price: string, stock: int, min_stock: int, is_low: bool}>
   */
  public function listByGondola(int $gondolaId): array
  {
    $stmt = $this->database->getConnection()->prepare(
      "SELECT id, name, price, stock, min_stock
       FROM products
       WHERE gondola_id = :gondola_id AND active = 1
       ORDER BY name ASC",
    );
    $stmt->execute(["gondola_id" => $gondolaId]);

    return array_map(
      static function (array $row): array {
        $stock = (int) $row["stock"];
        $minStock = (int) $row["min_stock"];

        return [
          "id" => (int) $row["id"],
          "name" => (string) $row["name"],
          "price" => (string) $row["price"],
          "stock" => $stock,
          "min_stock" => $minStock,
          "is_low" => $minStock > 0 && $stock <= $minStock,
        ];
      },
      $stmt->fetchAll(),
    );
  }

  /**
   * ¿Hay un producto ACTIVO con ese nombre en la góndola?
   * La colación utf8mb4_unicode_ci no distingue mayúsculas (ni tildes).
   */
  public function existsByName(int $gondolaId, string $name): bool
  {
    $stmt = $this->database->getConnection()->prepare(
      "SELECT 1 FROM products
       WHERE gondola_id = :gondola_id AND active = 1 AND name = :name
       LIMIT 1",
    );
    $stmt->execute(["gondola_id" => $gondolaId, "name" => $name]);

    return $stmt->fetchColumn() !== false;
  }

  /**
   * Crea el producto y, si el stock inicial es mayor a 0, su movimiento de
   * 'entrada', todo en una transacción. Devuelve el id del producto.
   * entry_date la completa la base con la fecha actual.
   */
  public function create(
    int $repositoryId,
    int $gondolaId,
    int $categoryId,
    string $name,
    string $price,
    int $stock,
    int $minStock,
    int $userId,
  ): int {
    $id = $this->database->runTransaction(
      static function (PDO $connection) use (
        $repositoryId,
        $gondolaId,
        $categoryId,
        $name,
        $price,
        $stock,
        $minStock,
        $userId,
      ): int {
        $stmt = $connection->prepare(
          "INSERT INTO products
             (repository_id, gondola_id, category_id, name, price, stock, min_stock, entry_date)
           VALUES
             (:repository_id, :gondola_id, :category_id, :name, :price, :stock, :min_stock, CURDATE())",
        );
        $stmt->execute([
          "repository_id" => $repositoryId,
          "gondola_id" => $gondolaId,
          "category_id" => $categoryId,
          "name" => $name,
          "price" => $price,
          "stock" => $stock,
          "min_stock" => $minStock,
        ]);

        $productId = (int) $connection->lastInsertId();

        if ($stock > 0) {
          $stmt = $connection->prepare(
            "INSERT INTO stock_movements (product_id, user_id, type, quantity, note)
             VALUES (:product_id, :user_id, 'entrada', :quantity, 'Stock inicial')",
          );
          $stmt->execute([
            "product_id" => $productId,
            "user_id" => $userId,
            "quantity" => $stock,
          ]);
        }

        return $productId;
      },
    );

    

    return (int) $id;
  }

    /**
   * Búsqueda de la caja: solo productos ACTIVOS del repositorio, por nombre (LIKE) y,
   * si el texto es numérico, también por código interno. Máximo 10 resultados.
   * Orden: nombre (si es numérico, el código exacto va primero).
   *
   * @return list<array{id: int, code: int, name: string, price_cents: int, stock: int, gondola_name: string}>
   */
  public function search(int $repositoryId, string $query, int $limit = 10): array
  {
    $connection = $this->database->getConnection();
    $limit = max(1, min(10, $limit));

    // '!' es el carácter de escape del LIKE: los % y _ que escriba el usuario se buscan literalmente
    $like = "%" . str_replace(["!", "%", "_"], ["!!", "!%", "!_"], $query) . "%";

    $select = "SELECT p.id, p.code, p.name, p.price, p.stock, g.name AS gondola_name
               FROM products p
               INNER JOIN gondolas g ON g.id = p.gondola_id";

    if (ctype_digit($query)) {
      $stmt = $connection->prepare(
        $select . "
         WHERE p.repository_id = :repository_id
           AND p.active = 1
           AND (p.code = :code OR p.name LIKE :like ESCAPE '!')
         ORDER BY (p.code = :code_first) DESC, p.name ASC
         LIMIT " . $limit,
      );
      $stmt->execute([
        "repository_id" => $repositoryId,
        "code" => (int) $query,
        "like" => $like,
        "code_first" => (int) $query,
      ]);
    } else {
      $stmt = $connection->prepare(
        $select . "
         WHERE p.repository_id = :repository_id
           AND p.active = 1
           AND p.name LIKE :like ESCAPE '!'
         ORDER BY p.name ASC
         LIMIT " . $limit,
      );
      $stmt->execute(["repository_id" => $repositoryId, "like" => $like]);
    }

    return array_map(
      static fn(array $row): array => [
        "id" => (int) $row["id"],
        "code" => (int) $row["code"],
        "name" => (string) $row["name"],
        "price_cents" => Money::fromDecimal((string) $row["price"]),
        "stock" => (int) $row["stock"],
        "gondola_name" => (string) $row["gondola_name"],
      ],
      $stmt->fetchAll(),
    );
  }

  /**
   * Datos frescos (precio y stock) de productos ACTIVOS de un repositorio.
   * Se usa para reconstruir el carrito cuando el servidor devuelve errores (422).
   *
   * @param list<int> $ids
   * @return list<array{id: int, code: int, name: string, price_cents: int, stock: int}>
   */
  public function findForCart(int $repositoryId, array $ids): array
  {
    if ($ids === []) {
      return [];
    }

    $marks = implode(",", array_fill(0, count($ids), "?"));

    $stmt = $this->database->getConnection()->prepare(
      "SELECT id, code, name, price, stock
       FROM products
       WHERE repository_id = ? AND active = 1 AND id IN ($marks)",
    );
    $stmt->execute([$repositoryId, ...array_values($ids)]);

    return array_map(
      static fn(array $row): array => [
        "id" => (int) $row["id"],
        "code" => (int) $row["code"],
        "name" => (string) $row["name"],
        "price_cents" => Money::fromDecimal((string) $row["price"]),
        "stock" => (int) $row["stock"],
      ],
      $stmt->fetchAll(),
    );
  }
  
}
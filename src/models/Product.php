<?php

declare(strict_types=1);

namespace App\Models;

use Database;
use PDO;
use RuntimeException;

final class Product
{
  public function __construct(private readonly Database $database) {}

  /**
   * Productos activos de una góndola, por nombre.
   * Filtra por repositorio Y góndola (el repositorio sale de la URL ya validada).
   * is_low = true cuando min_stock > 0 y stock <= min_stock.
   *
   * @return list<array{id: int, code: int, name: string, price: string, stock: int, min_stock: int, is_low: bool}>
   */
  public function listByGondola(int $repositoryId, int $gondolaId): array
  {
    $stmt = $this->database->getConnection()->prepare(
      "SELECT id, code, name, price, stock, min_stock
       FROM products
       WHERE repository_id = :repository_id
         AND gondola_id = :gondola_id
         AND active = 1
       ORDER BY name ASC",
    );
    $stmt->execute([
      "repository_id" => $repositoryId,
      "gondola_id" => $gondolaId,
    ]);

    return array_map(
      static function (array $row): array {
        $stock = (int) $row["stock"];
        $minStock = (int) $row["min_stock"];

        return [
          "id" => (int) $row["id"],
          "code" => (int) $row["code"],
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
   * Un producto ACTIVO, solo si pertenece a esa góndola Y a ese repositorio.
   *
   * @return array{id: int, code: int, name: string, stock: int}|null
   */
  public function findInGondola(int $productId, int $gondolaId, int $repositoryId): ?array
  {
    $stmt = $this->database->getConnection()->prepare(
      "SELECT id, code, name, stock
       FROM products
       WHERE id = :id
         AND gondola_id = :gondola_id
         AND repository_id = :repository_id
         AND active = 1
       LIMIT 1",
    );
    $stmt->execute([
      "id" => $productId,
      "gondola_id" => $gondolaId,
      "repository_id" => $repositoryId,
    ]);

    $row = $stmt->fetch();

    if ($row === false) {
      return null;
    }

    return [
      "id" => (int) $row["id"],
      "code" => (int) $row["code"],
      "name" => (string) $row["name"],
      "stock" => (int) $row["stock"],
    ];
  }

  /**
   * ¿Hay un producto ACTIVO con ese nombre en la góndola (de ese repositorio)?
   * La colación utf8mb4_unicode_ci no distingue mayúsculas (ni tildes).
   */
  public function existsByName(int $repositoryId, int $gondolaId, string $name): bool
  {
    $stmt = $this->database->getConnection()->prepare(
      "SELECT 1 FROM products
       WHERE repository_id = :repository_id
         AND gondola_id = :gondola_id
         AND active = 1
         AND name = :name
       LIMIT 1",
    );
    $stmt->execute([
      "repository_id" => $repositoryId,
      "gondola_id" => $gondolaId,
      "name" => $name,
    ]);

    return $stmt->fetchColumn() !== false;
  }

  /**
   * Crea el producto y, si el stock inicial es mayor a 0, su movimiento de
   * 'entrada', todo en una transacción. Devuelve el id del producto.
   * entry_date la completa la base con la fecha actual.
   *
   * El código interno (#1, #2...) se asigna dentro de la MISMA transacción:
   * el UPDATE del contador bloquea la fila del repositorio hasta el commit, así
   * dos altas simultáneas nunca obtienen el mismo número. Si algo falla, el
   * rollback también devuelve el contador: no quedan huecos.
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
        // LAST_INSERT_ID(expr) guarda en la sesión el valor ANTERIOR del contador
        // (el código a usar) y el SET lo incrementa, todo en una sola sentencia.
        $stmt = $connection->prepare(
          "UPDATE repositories
           SET next_product_code = LAST_INSERT_ID(next_product_code) + 1
           WHERE id = :repository_id",
        );
        $stmt->execute(["repository_id" => $repositoryId]);

        if ($stmt->rowCount() !== 1) {
          throw new RuntimeException("No se pudo reservar el código del producto.");
        }

        $query = $connection->query("SELECT LAST_INSERT_ID()");
        $code = (int) ($query === false ? 0 : $query->fetchColumn());

        if ($code < 1) {
          throw new RuntimeException("Código de producto inválido.");
        }

        $stmt = $connection->prepare(
          "INSERT INTO products
             (repository_id, code, gondola_id, category_id, name, price, stock, min_stock, entry_date)
           VALUES
             (:repository_id, :code, :gondola_id, :category_id, :name, :price, :stock, :min_stock, CURDATE())",
        );
        $stmt->execute([
          "repository_id" => $repositoryId,
          "code" => $code,
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
   * Ajuste manual de stock (solo admin; el permiso lo valida el controlador).
   *
   * En una transacción: bloquea el producto (FOR UPDATE), compara con el stock
   * que el usuario veía y, si todo coincide, actualiza y registra un movimiento
   * 'ajuste' con la diferencia (con signo, nunca 0).
   *
   * @return 'updated'|'unchanged'|'conflict'|'not_found'
   */
  public function adjustStock(
    int $repositoryId,
    int $gondolaId,
    int $productId,
    int $newStock,
    int $expectedStock,
    int $userId,
  ): string {
    $result = $this->database->runTransaction(
      static function (PDO $connection) use (
        $repositoryId,
        $gondolaId,
        $productId,
        $newStock,
        $expectedStock,
        $userId,
      ): string {
        $stmt = $connection->prepare(
          "SELECT stock FROM products
           WHERE id = :id
             AND gondola_id = :gondola_id
             AND repository_id = :repository_id
             AND active = 1
           FOR UPDATE",
        );
        $stmt->execute([
          "id" => $productId,
          "gondola_id" => $gondolaId,
          "repository_id" => $repositoryId,
        ]);

        $row = $stmt->fetch();

        if ($row === false) {
          return "not_found";
        }

        $current = (int) $row["stock"];

        // Otra pestaña/usuario cambió el stock mientras se editaba
        if ($current !== $expectedStock) {
          return "conflict";
        }

        if ($newStock === $current) {
          return "unchanged";
        }

        $stmt = $connection->prepare(
          "UPDATE products SET stock = :stock WHERE id = :id",
        );
        $stmt->execute(["stock" => $newStock, "id" => $productId]);

        $stmt = $connection->prepare(
          "INSERT INTO stock_movements (product_id, user_id, type, quantity, note)
           VALUES (:product_id, :user_id, 'ajuste', :quantity, 'Ajuste manual de stock')",
        );
        $stmt->execute([
          "product_id" => $productId,
          "user_id" => $userId,
          "quantity" => $newStock - $current,
        ]);

        return "updated";
      },
    );

    return (string) $result;
  }
}
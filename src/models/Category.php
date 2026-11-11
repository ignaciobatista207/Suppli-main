<?php

declare(strict_types=1);

namespace App\Models;

use Database;

final class Category
{
  public function __construct(private readonly Database $database) {}

  /**
   * Catálogo en dos niveles: categorías principales con sus subcategorías.
   *
   * @return list<array{id: int, name: string, children: list<array{id: int, name: string}>}>
   */
  public function tree(): array
  {
    $stmt = $this->database->getConnection()->query(
      "SELECT id, parent_id, name FROM categories ORDER BY sort_order ASC, name ASC",
    );

    $rows = $stmt === false ? [] : $stmt->fetchAll();

    $children = [];

    foreach ($rows as $row) {
      if ($row["parent_id"] !== null) {
        $children[(int) $row["parent_id"]][] = [
          "id" => (int) $row["id"],
          "name" => (string) $row["name"],
        ];
      }
    }

    $tree = [];

    foreach ($rows as $row) {
      if ($row["parent_id"] === null) {
        $id = (int) $row["id"];

        $tree[] = [
          "id" => $id,
          "name" => (string) $row["name"],
          "children" => $children[$id] ?? [],
        ];
      }
    }

    return $tree;
  }

  public function existsById(int $id): bool
  {
    $stmt = $this->database->getConnection()->prepare(
      "SELECT 1 FROM categories WHERE id = :id LIMIT 1",
    );
    $stmt->execute(["id" => $id]);

    return $stmt->fetchColumn() !== false;
  }

  /**
   * @return array{id: int, parent_id: int|null, name: string}|null
   */
  public function findById(int $id): ?array
  {
    $stmt = $this->database->getConnection()->prepare(
      "SELECT id, parent_id, name FROM categories WHERE id = :id LIMIT 1",
    );
    $stmt->execute(["id" => $id]);

    $row = $stmt->fetch();

    return $row === false ? null : $row;
  }
}
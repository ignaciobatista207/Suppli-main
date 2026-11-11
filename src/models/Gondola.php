<?php

declare(strict_types=1);

namespace App\Models;

use Database;

final class Gondola
{
  public function __construct(private readonly Database $database) {}

  /**
   * Góndolas de un repositorio, con su categoría y la categoría padre (si es subcategoría).
   *
   * @return list<array{id: int, name: string, color: string, category_name: string, parent_category_name: string|null}>
   */
  public function listByRepository(int $repoId): array
  {
    $stmt = $this->database->getConnection()->prepare(
      "SELECT g.id, g.name, g.color,
              c.name AS category_name,
              p.name AS parent_category_name
       FROM gondolas g
       INNER JOIN categories c ON c.id = g.category_id
       LEFT JOIN categories p ON p.id = c.parent_id
       WHERE g.repository_id = :repository_id
       ORDER BY g.name ASC",
    );
    $stmt->execute(["repository_id" => $repoId]);

    return array_map(
      static fn(array $row): array => [
        "id" => (int) $row["id"],
        "name" => (string) $row["name"],
        "color" => Palette::safe((string) $row["color"]),
        "category_name" => (string) $row["category_name"],
        "parent_category_name" => $row["parent_category_name"] === null
          ? null
          : (string) $row["parent_category_name"],
      ],
      $stmt->fetchAll(),
    );
  }

  public function existsByName(int $repoId, string $name): bool
  {
    $stmt = $this->database->getConnection()->prepare(
      "SELECT 1 FROM gondolas WHERE repository_id = :repository_id AND name = :name LIMIT 1",
    );
    $stmt->execute(["repository_id" => $repoId, "name" => $name]);

    return $stmt->fetchColumn() !== false;
  }

  /**
   * Crea la góndola y devuelve su id.
   * Lanza PDOException si el nombre ya existe en ese repositorio (UNIQUE).
   */
  public function create(int $repoId, int $categoryId, string $name, string $color): int
  {
    $connection = $this->database->getConnection();

    $stmt = $connection->prepare(
      "INSERT INTO gondolas (repository_id, category_id, name, color)
       VALUES (:repository_id, :category_id, :name, :color)",
    );
    $stmt->execute([
      "repository_id" => $repoId,
      "category_id" => $categoryId,
      "name" => $name,
      "color" => $color,
    ]);

    return (int) $connection->lastInsertId();
  }
}
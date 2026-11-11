<?php

declare(strict_types=1);

namespace App\Models;

use Database;
use PDO;

final class Repository
{
  public function __construct(private readonly Database $database) {}

  /**
   * ¿Ya hay un repositorio con ese nombre? (el nombre es único en toda la app)
   * $exceptId permite ignorar al propio repositorio al editarlo.
   */
  public function existsByName(string $name, ?int $exceptId = null): bool
  {
    $connection = $this->database->getConnection();

    if ($exceptId === null) {
      $stmt = $connection->prepare(
        "SELECT 1 FROM repositories WHERE name = :name LIMIT 1",
      );
      $stmt->execute(["name" => $name]);
    } else {
      $stmt = $connection->prepare(
        "SELECT 1 FROM repositories WHERE name = :name AND id <> :except_id LIMIT 1",
      );
      $stmt->execute(["name" => $name, "except_id" => $exceptId]);
    }

    return $stmt->fetchColumn() !== false;
  }

  /**
   * Crea el repositorio y deja al usuario como 'admin', todo en una transacción.
   * Lanza PDOException si el nombre ya existe (restricción UNIQUE).
   */
  public function create(
    string $name,
    string $passwordHash,
    string $color,
    int $userId,
  ): int {
    $id = $this->database->runTransaction(
      static function (PDO $connection) use ($name, $passwordHash, $color, $userId): int {
        $stmt = $connection->prepare(
          "INSERT INTO repositories (name, password_hash, color, created_by)
           VALUES (:name, :password_hash, :color, :created_by)",
        );
        $stmt->execute([
          "name" => $name,
          "password_hash" => $passwordHash,
          "color" => $color,
          "created_by" => $userId,
        ]);

        $repositoryId = (int) $connection->lastInsertId();

        $stmt = $connection->prepare(
          "INSERT INTO repository_members (repository_id, user_id, role)
           VALUES (:repository_id, :user_id, 'admin')",
        );
        $stmt->execute([
          "repository_id" => $repositoryId,
          "user_id" => $userId,
        ]);

        return $repositoryId;
      },
    );

    return (int) $id;
  }

  /**
   * Repositorios de los que el usuario es miembro, por nombre.
   *
   * @return list<array{id: int, name: string, color: string, role: string}>
   */
  public function listForUser(int $userId): array
  {
    $stmt = $this->database->getConnection()->prepare(
      "SELECT r.id, r.name, r.color, m.role
       FROM repositories r
       INNER JOIN repository_members m ON m.repository_id = r.id
       WHERE m.user_id = :user_id
       ORDER BY r.name ASC",
    );
    $stmt->execute(["user_id" => $userId]);

    return array_map(
      static fn(array $row): array => [
        "id" => (int) $row["id"],
        "name" => (string) $row["name"],
        "color" => Palette::safe((string) $row["color"]),
        "role" => (string) $row["role"],
      ],
      $stmt->fetchAll(),
    );
  }

  /**
   * El repositorio SOLO si el usuario es miembro (incluye su rol).
   * Nunca devuelve password_hash.
   *
   * @return array{id: int, name: string, color: string, role: string}|null
   */
  public function findForUser(int $repoId, int $userId): ?array
  {
    $stmt = $this->database->getConnection()->prepare(
      "SELECT r.id, r.name, r.color, m.role
       FROM repositories r
       INNER JOIN repository_members m ON m.repository_id = r.id
       WHERE r.id = :repository_id AND m.user_id = :user_id
       LIMIT 1",
    );
    $stmt->execute(["repository_id" => $repoId, "user_id" => $userId]);

    $row = $stmt->fetch();

    if ($row === false) {
      return null;
    }

    return [
      "id" => (int) $row["id"],
      "name" => (string) $row["name"],
      "color" => Palette::safe((string) $row["color"]),
      "role" => (string) $row["role"],
    ];
  }

  /**
   * Actualiza nombre y color; la contraseña solo si llega un hash nuevo.
   * Lanza PDOException si el nombre ya existe (restricción UNIQUE).
   */
  public function update(int $id, string $name, string $color, ?string $passwordHash): void
  {
    $connection = $this->database->getConnection();

    if ($passwordHash === null) {
      $stmt = $connection->prepare(
        "UPDATE repositories SET name = :name, color = :color WHERE id = :id",
      );
      $stmt->execute(["name" => $name, "color" => $color, "id" => $id]);

      return;
    }

    $stmt = $connection->prepare(
      "UPDATE repositories
       SET name = :name, color = :color, password_hash = :password_hash
       WHERE id = :id",
    );
    $stmt->execute([
      "name" => $name,
      "color" => $color,
      "password_hash" => $passwordHash,
      "id" => $id,
    ]);
  }
}
<?php

declare(strict_types=1);

namespace App\Models;

use Database;

final class User
{
  public function __construct(private readonly Database $database) {}

  /**
   * @return array{id: int, name: string, email: string, password_hash: string}|null
   */
  public function findByEmail(string $email): ?array
  {
    $stmt = $this->database->getConnection()->prepare(
      "SELECT id, name, email, password_hash FROM users WHERE email = :email LIMIT 1",
    );
    $stmt->execute(["email" => $email]);

    $row = $stmt->fetch();

    return $row === false ? null : $row;
  }

  /**
   * @return array{id: int, name: string, email: string}|null
   */
  public function findById(int $id): ?array
  {
    $stmt = $this->database->getConnection()->prepare(
      "SELECT id, name, email FROM users WHERE id = :id LIMIT 1",
    );
    $stmt->execute(["id" => $id]);

    $row = $stmt->fetch();

    return $row === false ? null : $row;
  }

  /**
   * Crea un usuario y devuelve su id.
   * Lanza PDOException si el correo ya existe (restricción UNIQUE).
   */
  public function create(string $name, string $email, string $passwordHash): int
  {
    $connection = $this->database->getConnection();

    $stmt = $connection->prepare(
      "INSERT INTO users (name, email, password_hash) VALUES (:name, :email, :password_hash)",
    );
    $stmt->execute([
      "name" => $name,
      "email" => $email,
      "password_hash" => $passwordHash,
    ]);

    return (int) $connection->lastInsertId();
  }
}
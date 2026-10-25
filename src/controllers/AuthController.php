<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Models\User;
use PDOException;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Views\PhpRenderer;

final class AuthController
{
  public function __construct(
    private readonly PhpRenderer $renderer,
    private readonly User $users,
  ) {}

  public function showLogin(Request $request, Response $response): Response
  {
    if (!empty($_SESSION["user_id"])) {
      return redirect($response, "/dashboard");
    }

    return $this->renderer->render($response, "auth/login.php");
  }

  public function showRegister(Request $request, Response $response): Response
  {
    if (!empty($_SESSION["user_id"])) {
      return redirect($response, "/dashboard");
    }

    return $this->renderer->render($response, "auth/registro.php");
  }

  public function login(Request $request, Response $response): Response
  {
    $data = (array) $request->getParsedBody();
    $email = mb_strtolower(trim((string) ($data["email"] ?? "")));
    $password = (string) ($data["password"] ?? "");

    $user = $email !== "" ? $this->users->findByEmail($email) : null;

    if ($user === null || !password_verify($password, $user["password_hash"])) {
      return $this->renderer->render(
        $response->withStatus(401),
        "auth/login.php",
        [
          "error" => "Correo o contraseña incorrectos.",
          "old" => ["email" => $email],
        ],
      );
    }

    $this->startSession((int) $user["id"]);

    return redirect($response, "/dashboard");
  }

  public function register(Request $request, Response $response): Response
  {
    $data = (array) $request->getParsedBody();
    $name = trim((string) ($data["name"] ?? ""));
    $email = mb_strtolower(trim((string) ($data["email"] ?? "")));
    $password = (string) ($data["password"] ?? "");
    $confirmation = (string) ($data["password_confirmation"] ?? "");

    $errors = [];

    if ($name === "") {
      $errors[] = "Ingresa tu nombre.";
    } elseif (mb_strlen($name) > 100) {
      $errors[] = "El nombre no puede superar los 100 caracteres.";
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
      $errors[] = "Ingresa un correo electrónico válido.";
    } elseif (mb_strlen($email) > 150) {
      $errors[] = "El correo no puede superar los 150 caracteres.";
    }

    if (mb_strlen($password) < 8) {
      $errors[] = "La contraseña debe tener al menos 8 caracteres.";
    } elseif (strlen($password) > 72) {
      // bcrypt ignora todo lo que pase de 72 bytes
      $errors[] = "La contraseña no puede superar los 72 caracteres.";
    } elseif ($password !== $confirmation) {
      $errors[] = "Las contraseñas no coinciden.";
    }

    if ($errors === [] && $this->users->findByEmail($email) !== null) {
      $errors[] = "Ese correo ya está registrado.";
    }

    $userId = 0;

    if ($errors === []) {
      try {
        $userId = $this->users->create(
          $name,
          $email,
          password_hash($password, PASSWORD_DEFAULT),
        );
      } catch (PDOException $e) {
        // SQLSTATE 23xxx = violación de restricción (correo duplicado por carrera)
        if (!str_starts_with((string) $e->getCode(), "23")) {
          throw $e;
        }

        $errors[] = "Ese correo ya está registrado.";
      }
    }

    if ($errors !== []) {
      return $this->renderer->render(
        $response->withStatus(422),
        "auth/registro.php",
        [
          "errors" => $errors,
          "old" => ["name" => $name, "email" => $email],
        ],
      );
    }

    $this->startSession($userId);

    return redirect($response, "/dashboard");
  }

  public function logout(Request $request, Response $response): Response
  {
    $_SESSION = [];

    if (ini_get("session.use_cookies")) {
      $params = session_get_cookie_params();

      setcookie(session_name(), "", [
        "expires" => time() - 42000,
        "path" => $params["path"],
        "domain" => $params["domain"],
        "secure" => $params["secure"],
        "httponly" => $params["httponly"],
        "samesite" => $params["samesite"] ?? "Lax",
      ]);
    }

    session_destroy();

    return redirect($response, "/login");
  }

  private function startSession(int $userId): void
  {
    // Evita fijación de sesión: nuevo id al autenticar
    session_regenerate_id(true);
    $_SESSION["user_id"] = $userId;
  }
}
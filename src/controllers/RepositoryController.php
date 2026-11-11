<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Models\Category;
use App\Models\Gondola;
use App\Models\Palette;
use App\Models\Repository;
use App\Models\User;
use PDOException;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Exception\HttpForbiddenException;
use Slim\Exception\HttpNotFoundException;
use Slim\Views\PhpRenderer;

final class RepositoryController
{
  private const DUPLICATE_NAME = "Ya existe un repositorio con ese nombre. Elige otro.";

  public function __construct(
    private readonly PhpRenderer $renderer,
    private readonly User $users,
    private readonly Repository $repositories,
    private readonly Gondola $gondolas,
    private readonly Category $categories,
    private readonly DashboardController $dashboard,
  ) {}

  /** GET /repositorios → el listado vive en el dashboard. */
  public function index(Request $request, Response $response): Response
  {
    return redirect($response, "/dashboard");
  }

  /** GET /repositorios/{id} */
  public function show(Request $request, Response $response, array $args): Response
  {
    return $this->renderShow($response, $this->memberOrFail($request, $args));
  }

  /** POST /repositorios */
  public function store(Request $request, Response $response): Response
  {
    $userId = (int) $_SESSION["user_id"];
    $data = (array) $request->getParsedBody();
    $name = trim((string) ($data["name"] ?? ""));
    $password = (string) ($data["password"] ?? "");

    $errors = [];

    $nameError = $this->nameError($name, null);
    if ($nameError !== null) {
      $errors[] = $nameError;
    }

    $passwordError = $this->passwordError($password);
    if ($passwordError !== null) {
      $errors[] = $passwordError;
    }

    $repositoryId = 0;

    if ($errors === []) {
      try {
        $repositoryId = $this->repositories->create(
          $name,
          password_hash($password, PASSWORD_DEFAULT),
          Palette::DEFAULT,
          $userId,
        );
      } catch (PDOException $e) {
        // SQLSTATE 23xxx = restricción (nombre duplicado por carrera)
        if (!str_starts_with((string) $e->getCode(), "23")) {
          throw $e;
        }

        $errors[] = self::DUPLICATE_NAME;
      }
    }

    if ($errors !== []) {
      return $this->dashboard->render(
        $response->withStatus(422),
        $userId,
        $errors,
        ["name" => $name],
      );
    }

    flash("Repositorio creado.");

    return redirect($response, "/repositorios/" . $repositoryId);
  }

  /** POST /repositorios/{id}/editar (solo admin) */
  public function update(Request $request, Response $response, array $args): Response
  {
    $repository = $this->adminOrFail($request, $args);
    $repositoryId = (int) $repository["id"];

    $data = (array) $request->getParsedBody();
    $name = trim((string) ($data["name"] ?? ""));
    $password = (string) ($data["password"] ?? "");
    $colorInput = (string) ($data["color"] ?? "");
    $color = Palette::normalize($colorInput);

    $errors = [];

    $nameError = $this->nameError($name, $repositoryId);
    if ($nameError !== null) {
      $errors[] = $nameError;
    }

    if ($color === null) {
      $errors[] = "Elige un color de la paleta.";
    }

    // La contraseña es opcional al editar: vacía = conservar la actual
    if ($password !== "") {
      $passwordError = $this->passwordError($password);
      if ($passwordError !== null) {
        $errors[] = $passwordError;
      }
    }

    if ($errors === [] && $color !== null) {
      try {
        $this->repositories->update(
          $repositoryId,
          $name,
          $color,
          $password !== "" ? password_hash($password, PASSWORD_DEFAULT) : null,
        );
      } catch (PDOException $e) {
        if (!str_starts_with((string) $e->getCode(), "23")) {
          throw $e;
        }

        $errors[] = self::DUPLICATE_NAME;
      }
    }

    if ($errors !== []) {
      return $this->renderShow(
        $response->withStatus(422),
        $repository,
        $errors,
        ["name" => $name, "color" => $colorInput],
        "edit",
      );
    }

    flash("Cambios guardados.");

    return redirect($response, "/repositorios/" . $repositoryId);
  }

  /**
   * El repositorio si el usuario de sesión es miembro; si no existe o no es
   * miembro → 404 (no se revela que existe).
   *
   * @return array{id: int, name: string, color: string, role: string}
   */
  public function memberOrFail(Request $request, array $args): array
  {
    $id = filter_var($args["id"] ?? null, FILTER_VALIDATE_INT, [
      "options" => ["min_range" => 1],
    ]);

    if ($id === false) {
      throw new HttpNotFoundException($request);
    }

    $repository = $this->repositories->findForUser($id, (int) $_SESSION["user_id"]);

    if ($repository === null) {
      throw new HttpNotFoundException($request);
    }

    return $repository;
  }

  /**
   * Igual que memberOrFail, pero además exige rol 'admin' (si no → 403).
   *
   * @return array{id: int, name: string, color: string, role: string}
   */
  public function adminOrFail(Request $request, array $args): array
  {
    $repository = $this->memberOrFail($request, $args);

    if ($repository["role"] !== "admin") {
      throw new HttpForbiddenException($request);
    }

    return $repository;
  }

  /**
   * Renderiza la página interna del repositorio. También la usan update() y
   * GondolaController::store() para volver con errores (422).
   *
   * @param array{id: int, name: string, color: string, role: string} $repository
   * @param list<string>         $errors
   * @param array<string, mixed> $old
   * @param string|null          $openDialog "edit" | "gondola" | null
   */
  public function renderShow(
    Response $response,
    array $repository,
    array $errors = [],
    array $old = [],
    ?string $openDialog = null,
  ): Response {
    $user = $this->users->findById((int) $_SESSION["user_id"]);

    if ($user === null) {
      unset($_SESSION["user_id"]);

      return redirect($response, "/login");
    }

    $isAdmin = $repository["role"] === "admin";

    return view(
      $this->renderer,
      $response,
      "repositories/show.php",
      [
        "title" => $repository["name"] . " | Suppli",
        "userName" => (string) $user["name"],
        "activeNav" => "repositorios",
        "repository" => $repository,
        "isAdmin" => $isAdmin,
        "gondolas" => $this->gondolas->listByRepository((int) $repository["id"]),
        "categoryTree" => $isAdmin ? $this->categories->tree() : [],
        "errors" => $errors,
        "old" => $old,
        "openDialog" => $openDialog,
      ],
      "layouts/app.php",
    );
  }

  private function nameError(string $name, ?int $exceptId): ?string
  {
    if ($name === "") {
      return "Ingresa el nombre del repositorio.";
    }

    if (mb_strlen($name) > 100) {
      return "El nombre no puede superar los 100 caracteres.";
    }

    if ($this->repositories->existsByName($name, $exceptId)) {
      return self::DUPLICATE_NAME;
    }

    return null;
  }

  // Mismas reglas que el registro de usuarios (mínimo 8; bcrypt ignora lo que pase de 72 bytes)
  private function passwordError(string $password): ?string
  {
    if (mb_strlen($password) < 8) {
      return "La contraseña debe tener al menos 8 caracteres.";
    }

    if (strlen($password) > 72) {
      return "La contraseña no puede superar los 72 caracteres.";
    }

    return null;
  }
}
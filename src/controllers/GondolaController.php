<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Models\Category;
use App\Models\Gondola;
use App\Models\Palette;
use App\Models\Product;
use App\Models\User;
use PDOException;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Exception\HttpNotFoundException;
use Slim\Views\PhpRenderer;

final class GondolaController
{
  private const DUPLICATE_NAME = "Ya existe una góndola con ese nombre en este repositorio.";

  public function __construct(
    private readonly PhpRenderer $renderer,
    private readonly User $users,
    private readonly RepositoryController $repositoryController,
    private readonly Gondola $gondolas,
    private readonly Product $products,
    private readonly Category $categories,
  ) {}

  /** GET /repositorios/{id}/gondolas/{gondolaId} (cualquier miembro) */
  public function show(Request $request, Response $response, array $args): Response
  {
    $repository = $this->repositoryController->memberOrFail($request, $args);
    $gondola = $this->gondolaOrFail($request, $args, $repository);

    return $this->renderShow($response, $repository, $gondola);
  }

  /** POST /repositorios/{id}/gondolas (solo admin) */
  public function store(Request $request, Response $response, array $args): Response
  {
    $repository = $this->repositoryController->adminOrFail($request, $args);
    $repositoryId = (int) $repository["id"];

    $data = (array) $request->getParsedBody();
    $name = trim((string) ($data["name"] ?? ""));
    $colorInput = (string) ($data["color"] ?? "");
    $color = Palette::normalize($colorInput);
    $categoryId = filter_var($data["category_id"] ?? null, FILTER_VALIDATE_INT, [
      "options" => ["min_range" => 1],
    ]);

    $errors = [];

    if ($name === "") {
      $errors[] = "Ingresa el nombre de la góndola.";
    } elseif (mb_strlen($name) > 100) {
      $errors[] = "El nombre no puede superar los 100 caracteres.";
    } elseif ($this->gondolas->existsByName($repositoryId, $name)) {
      $errors[] = self::DUPLICATE_NAME;
    }

    if ($color === null) {
      $errors[] = "Elige un color de la paleta.";
    }

    if ($categoryId === false || !$this->categories->existsById($categoryId)) {
      $errors[] = "Elige una categoría válida.";
    }

    if ($errors === [] && $color !== null && $categoryId !== false) {
      try {
        $this->gondolas->create($repositoryId, $categoryId, $name, $color);
      } catch (PDOException $e) {
        // SQLSTATE 23xxx = restricción (nombre duplicado por carrera)
        if (!str_starts_with((string) $e->getCode(), "23")) {
          throw $e;
        }

        $errors[] = self::DUPLICATE_NAME;
      }
    }

    if ($errors !== []) {
      return $this->repositoryController->renderShow(
        $response->withStatus(422),
        $repository,
        $errors,
        [
          "name" => $name,
          "color" => $colorInput,
          "category_id" => $categoryId === false ? 0 : $categoryId,
        ],
        "gondola",
      );
    }

    flash("Góndola creada.");

    return redirect($response, "/repositorios/" . $repositoryId);
  }

  /**
   * La góndola si pertenece al repositorio de la URL; si no existe, el id es
   * inválido o es de otro repositorio → 404.
   *
   * @param array{id: int, name: string, color: string, role: string} $repository
   * @return array{id: int, repository_id: int, category_id: int, name: string, color: string, category_name: string, parent_category_name: string|null}
   */
  public function gondolaOrFail(Request $request, array $args, array $repository): array
  {
    $id = filter_var($args["gondolaId"] ?? null, FILTER_VALIDATE_INT, [
      "options" => ["min_range" => 1],
    ]);

    if ($id === false) {
      throw new HttpNotFoundException($request);
    }

    $gondola = $this->gondolas->findInRepository($id, (int) $repository["id"]);

    if ($gondola === null) {
      throw new HttpNotFoundException($request);
    }

    return $gondola;
  }

   /**
   * Renderiza la página de la góndola. También la usa ProductController
   * (store y updateStock) para volver con errores (422).
   *
   * @param array{id: int, name: string, color: string, role: string} $repository
   * @param array<string, mixed> $gondola
   * @param list<string>         $errors       Errores de la viñeta "Agregar producto".
   * @param array<string, mixed> $old
   * @param list<string>         $stockErrors  Errores de la edición manual de stock.
   */
  public function renderShow(
    Response $response,
    array $repository,
    array $gondola,
    array $errors = [],
    array $old = [],
    bool $openDialog = false,
    array $stockErrors = [],
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
      "gondolas/show.php",
      [
        "title" => $gondola["name"] . " | Suppli",
        "userName" => (string) $user["name"],
        "activeNav" => "repositorios",
        "repository" => $repository,
        "gondola" => $gondola,
        "isAdmin" => $isAdmin,
        "products" => $this->products->listByGondola(
          (int) $repository["id"],
          (int) $gondola["id"],
        ),
        "categoryOptions" => $isAdmin
          ? $this->categories->optionsForGondola((int) $gondola["category_id"])
          : [],
        "errors" => $errors,
        "old" => $old,
        "openDialog" => $openDialog,
        "stockErrors" => $stockErrors,
      ],
      "layouts/app.php",
    );
  }
}
<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Models\Category;
use App\Models\Gondola;
use App\Models\Palette;
use PDOException;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

final class GondolaController
{
  private const DUPLICATE_NAME = "Ya existe una góndola con ese nombre en este repositorio.";

  public function __construct(
    private readonly RepositoryController $repositoryController,
    private readonly Gondola $gondolas,
    private readonly Category $categories,
  ) {}

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
}
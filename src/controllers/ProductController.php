<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Models\Category;
use App\Models\Money;
use App\Models\Product;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use RuntimeException;

final class ProductController
{
  // Máximo de una columna INT firmada de MySQL
  private const MAX_COUNT = 2147483647;

  public function __construct(
    private readonly RepositoryController $repositoryController,
    private readonly GondolaController $gondolaController,
    private readonly Category $categories,
    private readonly Product $products,
  ) {}

  /** POST /repositorios/{id}/gondolas/{gondolaId}/productos (solo admin) */
  public function store(Request $request, Response $response, array $args): Response
  {
    // 404 si no es miembro o la góndola no es de ese repositorio; 403 si no es admin
    $repository = $this->repositoryController->adminOrFail($request, $args);
    $gondola = $this->gondolaController->gondolaOrFail($request, $args, $repository);

    // repository_id y gondola_id salen SIEMPRE de la góndola cargada de la base
    $repositoryId = (int) $gondola["repository_id"];
    $gondolaId = (int) $gondola["id"];

    $options = $this->categories->optionsForGondola((int) $gondola["category_id"]);

    if ($options === []) {
      throw new RuntimeException("La góndola no tiene una categoría válida.");
    }

    $data = (array) $request->getParsedBody();
    $name = trim((string) ($data["name"] ?? ""));
    $priceInput = trim((string) ($data["price"] ?? ""));
    $stockInput = trim((string) ($data["stock"] ?? ""));
    $minStockInput = trim((string) ($data["min_stock"] ?? ""));
    $categoryInput = trim((string) ($data["category_id"] ?? ""));

    $price = Money::parse($priceInput);
    $stock = self::parseCount($stockInput);
    $minStock = self::parseCount($minStockInput);

    $errors = [];

    if ($name === "") {
      $errors[] = "Ingresa el nombre del producto.";
    } elseif (mb_strlen($name) > 150) {
      $errors[] = "El nombre no puede superar los 150 caracteres.";
    } elseif ($this->products->existsByName($gondolaId, $name)) {
      $errors[] = "Ya existe un producto con ese nombre en esta góndola.";
    }

    if ($price === null) {
      $errors[] = "Ingresa un precio mayor a 0, con hasta 2 decimales y sin separador de miles. "
        . "Por ejemplo: 1234,50. Máximo: 99999999,99.";
    }

    if ($stock === null) {
      $errors[] = "El stock actual debe ser un número entero de 0 o más.";
    }

    if ($minStock === null) {
      $errors[] = "El stock mínimo debe ser un número entero de 0 o más.";
    }

    // El servidor decide las categorías válidas a partir de la góndola.
    $allowedIds = array_column($options, "id");
    $categoryId = $allowedIds[0];

    if (count($options) > 1) {
      $posted = filter_var($categoryInput, FILTER_VALIDATE_INT, [
        "options" => ["min_range" => 1],
      ]);

      if ($posted === false || !in_array($posted, $allowedIds, true)) {
        $errors[] = "Elige una categoría válida.";
      } else {
        $categoryId = $posted;
      }
    }

    if ($errors !== [] || $price === null || $stock === null || $minStock === null) {
      return $this->gondolaController->renderShow(
        $response->withStatus(422),
        $repository,
        $gondola,
        $errors,
        [
          "name" => $name,
          "price" => $priceInput,
          "stock" => $stockInput,
          "min_stock" => $minStockInput,
          "category_id" => $categoryInput,
        ],
        true,
      );
    }

    $this->products->create(
      $repositoryId,
      $gondolaId,
      $categoryId,
      $name,
      $price,
      $stock,
      $minStock,
      (int) $_SESSION["user_id"],
    );

    flash("Producto guardado.");

    return redirect($response, "/repositorios/" . $repositoryId . "/gondolas/" . $gondolaId);
  }

  /**
   * Entero ≥ 0 (solo dígitos). Null si el texto no es un entero válido o excede el INT de MySQL.
   */
  private static function parseCount(string $value): ?int
  {
    if (!preg_match('/^\d{1,10}$/', $value)) {
      return null;
    }

    $number = (int) $value;

    return $number <= self::MAX_COUNT ? $number : null;
  }
}
<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Models\Repository;
use App\Models\User;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Views\PhpRenderer;

final class DashboardController
{
  public function __construct(
    private readonly PhpRenderer $renderer,
    private readonly User $users,
    private readonly Repository $repositories,
  ) {}

  public function index(Request $request, Response $response): Response
  {
    return $this->render($response, (int) $_SESSION["user_id"]);
  }

  /**
   * Renderiza el dashboard. También lo usa RepositoryController para
   * volver a mostrar la página con errores (422) al fallar "Crear repositorio".
   *
   * @param list<string>         $errors
   * @param array<string, mixed> $old
   */
  public function render(
    Response $response,
    int $userId,
    array $errors = [],
    array $old = [],
  ): Response {
    $user = $this->users->findById($userId);

    // Sesión viva de un usuario que ya no existe
    if ($user === null) {
      unset($_SESSION["user_id"]);

      return redirect($response, "/login");
    }

    return view(
      $this->renderer,
      $response,
      "dashboard/index.php",
      [
        "title" => "Inicio | Suppli",
        "userName" => (string) $user["name"],
        "activeNav" => "inicio",
        "repositories" => $this->repositories->listForUser($userId),
        "errors" => $errors,
        "old" => $old,
      ],
      "layouts/app.php",
    );
  }
}
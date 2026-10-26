<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Models\User;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Views\PhpRenderer;

final class DashboardController
{
  public function __construct(
    private readonly PhpRenderer $renderer,
    private readonly User $users,
  ) {}

  public function index(Request $request, Response $response): Response
  {
    $user = $this->users->findById((int) $_SESSION["user_id"]);

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
        "errors" => [],
        "old" => [],
      ],
      "layouts/app.php",
    );
  }
}
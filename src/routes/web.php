<?php

use App\Controllers\AuthController;
use App\Controllers\DashboardController;
use App\Middleware\RequireAuth;
use App\Models\User;
use Slim\App;
use Slim\Views\PhpRenderer;

return static function (App $app, PhpRenderer $renderer, Database $database): void {
  $users = new User($database);
  $auth = new AuthController($renderer, $users);
  $dashboard = new DashboardController($renderer, $users);
  $requireAuth = new RequireAuth($app->getResponseFactory());

  // Landing
  $app->get("/", function ($request, $response) use ($renderer) {
    if (!empty($_SESSION["user_id"])) {
      return redirect($response, "/dashboard");
    }

    return $renderer->render($response, "index.php");
  });

  // Autenticación
  $app->get("/login", [$auth, "showLogin"]);
  $app->post("/login", [$auth, "login"]);
  $app->get("/register", [$auth, "showRegister"]);
  $app->post("/register", [$auth, "register"]);
  $app->post("/logout", [$auth, "logout"])->add($requireAuth);

  // Área privada
  $app->get("/dashboard", [$dashboard, "index"])->add($requireAuth);
};
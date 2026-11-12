<?php

use App\Controllers\AuthController;
use App\Controllers\DashboardController;
use App\Controllers\GondolaController;
use App\Controllers\ProductController;
use App\Controllers\RepositoryController;
use App\Middleware\RequireAuth;
use App\Models\Category;
use App\Models\Gondola;
use App\Models\Product;
use App\Models\Repository;
use App\Models\User;
use Slim\App;
use Slim\Routing\RouteCollectorProxy;
use Slim\Views\PhpRenderer;

return static function (App $app, PhpRenderer $renderer, Database $database): void {
  $users = new User($database);
  $repositories = new Repository($database);
  $gondolas = new Gondola($database);
  $categories = new Category($database);
  $products = new Product($database);

  $auth = new AuthController($renderer, $users);
  $dashboard = new DashboardController($renderer, $users, $repositories);
  $repositoryController = new RepositoryController(
    $renderer,
    $users,
    $repositories,
    $gondolas,
    $categories,
    $dashboard,
  );
  $gondolaController = new GondolaController(
    $renderer,
    $users,
    $repositoryController,
    $gondolas,
    $products,
    $categories,
  );
  $productController = new ProductController(
    $repositoryController,
    $gondolaController,
    $categories,
    $products,
  );
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

  // Área privada (todo lo que está dentro del grupo exige sesión)
  $app->group("", function (RouteCollectorProxy $group) use (
    $auth,
    $dashboard,
    $repositoryController,
    $gondolaController,
    $productController,
  ) {
    $group->post("/logout", [$auth, "logout"]);

    $group->get("/dashboard", [$dashboard, "index"]);

    $group->get("/repositorios", [$repositoryController, "index"]);
    $group->post("/repositorios", [$repositoryController, "store"]);
    $group->get("/repositorios/{id:[0-9]+}", [$repositoryController, "show"]);
    $group->post("/repositorios/{id:[0-9]+}/editar", [$repositoryController, "update"]);
    $group->post("/repositorios/{id:[0-9]+}/gondolas", [$gondolaController, "store"]);

    $group->get(
      "/repositorios/{id:[0-9]+}/gondolas/{gondolaId:[0-9]+}",
      [$gondolaController, "show"],
    );
    $group->post(
      "/repositorios/{id:[0-9]+}/gondolas/{gondolaId:[0-9]+}/productos",
      [$productController, "store"],
    );

    // TODO: /caja (vista aparte, sin layouts/app.php)
  })->add($requireAuth);
};
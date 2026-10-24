<?php

use Slim\Factory\AppFactory;
use Slim\Views\PhpRenderer;
use Dotenv\Dotenv;

require __DIR__ . '/../vendor/autoload.php';

// Cargar variables de entorno desde el .env
Dotenv::createImmutable(__DIR__ . '/..')->safeLoad();

$env = $_ENV["APP_ENV"] ?? "prod";
$allowedEnvs = ["dev", "prod"];

if (!in_array($env, $allowedEnvs, true)) {
  throw new RuntimeException("APP_ENV inválido: $env");
}

$debug = $env === "dev";

// Crear la aplicacion de Slim
$app = AppFactory::create();

// Crear el motor de plantillas
$renderer = new PhpRenderer(
  templatePath: __DIR__ . "/views",
  attributes: ["title" => "Suppli"],
);

// --- Rutas de la fase 1 (landing y autenticación) ---
// Estas 3 vistas son documentos HTML completos, por eso se renderizan
// directo con $renderer->render() y NO pasan por view()/layouts/base.php.

// Landing
$app->get("/", function ($request, $response) use ($renderer) {
  return $renderer->render($response, "index.php");
});

// Login
$app->get("/login", function ($request, $response) use ($renderer) {
  return $renderer->render($response, "auth/login.php");
});

// Registro
$app->get("/register", function ($request, $response) use ($renderer) {
  return $renderer->render($response, "auth/registro.php");
});

// TODO (fase 2): POST /login    → validar credenciales, iniciar sesión
//                                  y, si falla, volver a renderizar con $error y $old.
// TODO (fase 2): POST /register → validar datos, crear usuario
//                                  y, si falla, volver a renderizar con $errors y $old.

$app->addErrorMiddleware($debug, true, true);

return $app;
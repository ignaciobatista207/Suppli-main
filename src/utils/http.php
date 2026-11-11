<?php

use Psr\Http\Message\ResponseInterface;

/**
 * Redirige a otra ruta interna.
 *
 * @param  ResponseInterface $response  La respuesta HTTP base.
 * @param  string            $to        Ruta de destino (e.g. "/dashboard").
 * @param  int               $status    Código HTTP de la redirección. Por defecto: 302.
 *
 * @return ResponseInterface La respuesta con el encabezado Location.
 */
function redirect(ResponseInterface $response, string $to, int $status = 302): ResponseInterface
{
  return $response->withHeader("Location", $to)->withStatus($status);
}

/**
 * Guarda un mensaje para mostrarlo en la próxima página (patrón POST → redirect).
 */
function flash(string $message): void
{
  $_SESSION["flash"] = $message;
}

/**
 * Devuelve el mensaje guardado con flash() y lo borra. Null si no hay.
 */
function pull_flash(): ?string
{
  $message = $_SESSION["flash"] ?? null;
  unset($_SESSION["flash"]);

  return is_string($message) && $message !== "" ? $message : null;
}
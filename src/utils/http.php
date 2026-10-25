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
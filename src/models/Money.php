<?php

declare(strict_types=1);

namespace App\Models;

/**
 * Precios: lectura de lo que escribe el usuario y formato para mostrar.
 */
final class Money
{
  /**
   * Convierte "1234,50" o "1234.50" en "1234.50" (apto para DECIMAL(10,2)).
   *
   * Devuelve null si el texto es ambiguo o inválido: negativo, cero, con
   * separador de miles ("1.234,50"), con más de 2 decimales o mayor a 99.999.999,99.
   */
  public static function parse(string $input): ?string
  {
    $input = trim($input);

    if (!preg_match('/^(\d{1,8})(?:[.,](\d{1,2}))?$/', $input, $m)) {
      return null;
    }

    $whole = (int) $m[1];
    $fraction = isset($m[2]) ? (int) str_pad($m[2], 2, "0") : 0;

    if ($whole === 0 && $fraction === 0) {
      return null;
    }

    return sprintf("%d.%02d", $whole, $fraction);
  }

  /**
   * "1234.5" → "$ 1.234,50" (formato es-AR).
   */
  public static function format(string|int|float $amount): string
  {
    return "$ " . number_format((float) $amount, 2, ",", ".");
  }
}
<?php

declare(strict_types=1);

namespace App\Models;

/**
 * Dinero: lectura de lo que escribe el usuario y formato para mostrar.
 *
 * Los métodos que terminan en "Cents" trabajan con centavos ENTEROS (1234,50 → 123450):
 * nunca se usan floats para calcular. Los DECIMAL de la base llegan como texto ("1234.50").
 */
final class Money
{
  /** Máximo que acepta un campo que escribe el usuario: 99.999.999,99 */
  public const MAX_INPUT_CENTS = 9999999999;

  /** Máximo de un subtotal / total de venta (columnas DECIMAL(12,2)): 9.999.999.999,99 */
  public const MAX_TOTAL_CENTS = 999999999999;

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

  /**
   * "1234,50" / "1234.50" → 123450 (centavos). Null si es inválido: con separador de
   * miles, más de 2 decimales, negativo o mayor a 99.999.999,99.
   * $allowZero = true acepta "0".
   */
  public static function parseCents(string $input, bool $allowZero = false): ?int
  {
    if (!preg_match('/^(\d{1,8})(?:[.,](\d{1,2}))?$/', trim($input), $m)) {
      return null;
    }

    $cents = (int) $m[1] * 100 + (isset($m[2]) ? (int) str_pad($m[2], 2, "0") : 0);

    if ($cents === 0 && !$allowZero) {
      return null;
    }

    return $cents;
  }

  /**
   * Un DECIMAL de la base ("1234.50", "0", "-15.00") → centavos (123450).
   */
  public static function fromDecimal(string $decimal): int
  {
    if (!preg_match('/^(-)?(\d+)(?:\.(\d+))?$/', trim($decimal), $m)) {
      return 0;
    }

    $fraction = isset($m[3]) ? (int) str_pad(substr($m[3], 0, 2), 2, "0") : 0;
    $cents = (int) $m[2] * 100 + $fraction;

    return $m[1] === "-" ? -$cents : $cents;
  }

  /**
   * Centavos → texto para guardar en un DECIMAL: 123450 → "1234.50".
   */
  public static function toDecimal(int $cents): string
  {
    $abs = abs($cents);

    return sprintf("%s%d.%02d", $cents < 0 ? "-" : "", intdiv($abs, 100), $abs % 100);
  }

  /**
   * Centavos → "$ 1.234,50" (formato es-AR). Los negativos llevan "-" delante.
   */
  public static function formatCents(int $cents): string
  {
    $abs = abs($cents);

    return ($cents < 0 ? "-" : "")
      . "$ " . number_format(intdiv($abs, 100), 0, ",", ".")
      . "," . sprintf("%02d", $abs % 100);
  }

  /**
   * Porcentaje "12,5" → 1250 (centésimas de punto porcentual, 0 a 10000 = 0 % a 100 %).
   * Hasta 2 decimales. Null si es inválido o mayor a 100.
   */
  public static function parsePercent(string $input): ?int
  {
    if (!preg_match('/^(\d{1,3})(?:[.,](\d{1,2}))?$/', trim($input), $m)) {
      return null;
    }

    $hundredths = (int) $m[1] * 100 + (isset($m[2]) ? (int) str_pad($m[2], 2, "0") : 0);

    return $hundredths <= 10000 ? $hundredths : null;
  }

  /**
   * Porcentaje de un monto en centavos, con redondeo half-up a centavos:
   * (centavos × centésimas + 5000) / 10000. Todo entero, sin floats.
   */
  public static function percentOf(int $cents, int $hundredths): int
  {
    return intdiv($cents * $hundredths + 5000, 10000);
  }
}
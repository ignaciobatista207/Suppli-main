<?php

declare(strict_types=1);

namespace App\Models;

use DateTimeImmutable;

/**
 * Fechas: formato para mostrar y validación de fechas de filtros.
 * La base (sesión en UTC-3) y PHP usan la misma hora, así que no hay conversión.
 */
final class Dates
{
  /**
   * "2026-11-14 15:03:09" → "14/11/2026 15:03:09". "—" si no hay fecha.
   */
  public static function format(?string $timestamp, string $format = "d/m/Y H:i:s"): string
  {
    if ($timestamp === null || $timestamp === "") {
      return "—";
    }

    $time = strtotime($timestamp);

    return $time === false ? "—" : date($format, $time);
  }

  /**
   * Valida "AAAA-MM-DD" (una fecha real: "2026-02-31" se rechaza). Devuelve la misma cadena o null.
   */
  public static function parseDate(string $input): ?string
  {
    $input = trim($input);
    $date = DateTimeImmutable::createFromFormat("!Y-m-d", $input);

    return $date !== false && $date->format("Y-m-d") === $input ? $input : null;
  }
}
<?php

declare(strict_types=1);

namespace App\Models;

/**
 * Paleta fija de colores para repositorios y góndolas.
 * Sin rojos: el rojo es exclusivo de "Cerrar sesión".
 * Todos superan 4,5:1 de contraste con texto blanco.
 */
final class Palette
{
  public const DEFAULT = "#294370";

  /** @var array<string, string> hex en mayúsculas => nombre */
  public const COLORS = [
    "#294370" => "Azul Suppli",
    "#111111" => "Grafito",
    "#2F6B4F" => "Verde pino",
    "#9A4F3A" => "Terracota",
    "#5E4B8B" => "Violeta",
    "#9A6008" => "Ocre",
    "#3E7CA6" => "Azul acero",
    "#5B6770" => "Gris pizarra",
  ];

  /**
   * Devuelve el hex en mayúsculas si pertenece a la paleta; null si no.
   */
  public static function normalize(?string $hex): ?string
  {
    $hex = strtoupper(trim((string) $hex));

    return isset(self::COLORS[$hex]) ? $hex : null;
  }

  public static function isValid(?string $hex): bool
  {
    return self::normalize($hex) !== null;
  }

  /**
   * Hex válido para imprimir en HTML: el color si es de la paleta, si no el por defecto.
   */
  public static function safe(?string $hex): string
  {
    return self::normalize($hex) ?? self::DEFAULT;
  }
}
<?php
/**
 * src/views/partials/color-picker.php
 *
 * Selector de color: paleta fija de 8 muestras (radios). Se usa con:
 *   <?= $this->fetch("partials/color-picker.php", ["idPrefix" => "edit", "selected" => $hex]) ?>
 *
 * Variables:
 *   $idPrefix  string       Prefijo único por formulario (evita ids repetidos).
 *   $selected  string|null  Hex seleccionado (si no es de la paleta, se usa Azul Suppli).
 *   $pickerName string      name del campo (por defecto "color").
 */
use App\Models\Palette;

$idPrefix = (string) ($idPrefix ?? "color");
$pickerName = (string) ($pickerName ?? "color");
$selected = Palette::normalize((string) ($selected ?? "")) ?? Palette::DEFAULT;
$i = 0;
?>
<fieldset>
  <legend class="mb-2 text-sm font-semibold">Color</legend>
  <div class="flex flex-wrap gap-3">
    <?php foreach (Palette::COLORS as $hex => $colorName): ?>
      <?php $i++; $id = $idPrefix . "-color-" . $i; ?>
      <div class="relative h-9 w-9">
        <input type="radio" id="<?= html($id) ?>" name="<?= html($pickerName) ?>"
               value="<?= html($hex) ?>" aria-label="<?= html($colorName) ?>"
               class="peer sr-only" <?= $hex === $selected ? "checked" : "" ?>>
        <label for="<?= html($id) ?>" title="<?= html($colorName) ?>"
               class="block h-9 w-9 cursor-pointer rounded-full border border-black/20 ring-offset-2 ring-offset-card peer-checked:ring-2 peer-checked:ring-ink peer-focus-visible:ring-2 peer-focus-visible:ring-ink"
               style="background-color: <?= html($hex) ?>"></label>
        <svg class="pointer-events-none absolute inset-0 m-auto hidden h-5 w-5 text-white peer-checked:block"
             viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"
             stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
          <path d="M5 13l4 4L19 7"/>
        </svg>
      </div>
    <?php endforeach; ?>
  </div>
</fieldset>
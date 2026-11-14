<?php
/**
 * src/views/caja/abrir.php
 *
 * Pantalla "Abrir caja" (no hay turno abierto). Se renderiza dentro de layouts/caja.php.
 *
 * Variables:
 *   $repository  array     id, name.
 *   $errors      string[]  Errores de validación.
 *   $old         array     ['opening_amount' => '...'].
 *   $csrf        array|null
 */
$repository = is_array($repository ?? null) ? $repository : [];
$errors     = is_array($errors ?? null) ? $errors : [];
$old        = is_array($old ?? null) ? $old : [];
$csrf       = $csrf ?? null;
$repoId     = (int) $repository["id"];

$focusRing  = "focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-ink";
$btnPrimary = "inline-flex min-h-[48px] w-full items-center justify-center rounded-lg bg-accent px-5 py-2.5 text-base font-semibold text-onaccent transition-colors hover:bg-accenth " . $focusRing;
$input      = "w-full rounded-lg border border-ink/30 bg-card px-3.5 py-3 text-lg text-ink focus:border-ink focus:outline-none focus:ring-2 focus:ring-ink/30";
?>
<div class="mx-auto w-full max-w-lg px-4 py-10">
  <section aria-labelledby="abrir-titulo" class="rounded-2xl border border-ink/20 bg-card p-6 shadow-sm">
    <h1 id="abrir-titulo" class="font-display text-3xl font-extrabold tracking-tight">Abrir caja</h1>
    <p class="mt-2 text-ink/80">
      No hay un turno abierto en <strong><?= html((string) $repository["name"]) ?></strong>.
      Indica con cuánto efectivo empieza la caja para poder vender.
    </p>

    <?php if ($errors): ?>
      <div role="alert" class="mt-5 rounded-lg border border-danger/40 bg-danger/10 px-4 py-3 text-sm text-danger">
        <p class="font-semibold">Revisa estos datos:</p>
        <ul class="mt-1 list-disc space-y-0.5 pl-5">
          <?php foreach ($errors as $msg): ?>
            <li><?= html((string) $msg) ?></li>
          <?php endforeach; ?>
        </ul>
      </div>
    <?php endif; ?>

    <!-- TODO (pendiente): token CSRF en este formulario -->
    <form action="/repositorios/<?= $repoId ?>/caja/abrir" method="POST" class="mt-6 space-y-5">
      <?php if (is_array($csrf) && isset($csrf["name"], $csrf["value"])): ?>
        <input type="hidden" name="<?= html((string) $csrf["name"]) ?>" value="<?= html((string) $csrf["value"]) ?>">
      <?php endif; ?>

      <div>
        <label for="opening-amount" class="mb-1.5 block text-sm font-semibold">Monto de apertura (efectivo en caja)</label>
        <input type="text" id="opening-amount" name="opening_amount" required autofocus
               inputmode="decimal" autocomplete="off" placeholder="0,00"
               aria-describedby="opening-hint"
               value="<?= html((string) ($old["opening_amount"] ?? "0")) ?>"
               class="<?= $input ?> tabular-nums">
        <p id="opening-hint" class="mt-1.5 text-sm text-ink/80">
          Usa coma o punto para los decimales, sin separador de miles. Escribe 0 si la caja empieza vacía.
        </p>
      </div>

      <button type="submit" class="<?= $btnPrimary ?>">Abrir caja</button>
    </form>

    <p class="mt-6 text-sm">
      <a href="/dashboard" class="rounded underline-offset-2 hover:underline <?= $focusRing ?>"><span aria-hidden="true">←</span> Volver al inicio</a>
    </p>
  </section>
</div>
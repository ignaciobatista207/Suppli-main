<?php
/**
 * src/views/caja/cierre.php
 *
 * Cierre del turno: resumen + formulario, o (si $closed) el resultado del cierre.
 * Se renderiza dentro de layouts/caja.php.
 *
 * Variables:
 *   $repository     array       id, name.
 *   $session        array|null  Turno abierto (modo formulario).
 *   $closed         array|null  Turno recién cerrado (modo resultado).
 *   $summary        array|null  count, total_cents, by_method[método] = {count, total_cents}.
 *   $expectedCents  int         Efectivo esperado = apertura + ventas en efectivo.
 *   $errors         string[]
 *   $old            array       counted_cash, close_note.
 *   $csrf           array|null
 */
use App\Models\Dates;
use App\Models\Money;

$repository    = is_array($repository ?? null) ? $repository : [];
$session       = is_array($session ?? null) ? $session : null;
$closed        = is_array($closed ?? null) ? $closed : null;
$summary       = is_array($summary ?? null) ? $summary : null;
$expectedCents = (int) ($expectedCents ?? 0);
$errors        = is_array($errors ?? null) ? $errors : [];
$old           = is_array($old ?? null) ? $old : [];
$csrf          = $csrf ?? null;
$repoId        = (int) $repository["id"];

$methods = ["efectivo" => "Efectivo", "transferencia" => "Transferencia", "tarjeta" => "Tarjeta"];

$focusRing  = "focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-ink";
$btnPrimary = "inline-flex min-h-[48px] items-center justify-center rounded-lg bg-accent px-6 py-2.5 text-base font-semibold text-onaccent transition-colors hover:bg-accenth " . $focusRing;
$btnSecondary = "inline-flex min-h-[48px] items-center justify-center rounded-lg border border-ink/30 px-6 py-2.5 text-base font-semibold text-ink transition-colors hover:bg-ink/10 " . $focusRing;
$input      = "w-full rounded-lg border border-ink/30 bg-card px-3.5 py-3 text-lg text-ink focus:border-ink focus:outline-none focus:ring-2 focus:ring-ink/30";

// "Sobran $ X" / "Faltan $ X" / "Caja justa": texto claro, sin depender del color
$diffLabel = static function (int $cents): string {
  if ($cents > 0) {
    return "Sobran " . Money::formatCents($cents);
  }

  if ($cents < 0) {
    return "Faltan " . Money::formatCents(abs($cents));
  }

  return "Caja justa";
};
?>
<div class="mx-auto w-full max-w-3xl px-4 py-8">

<?php if ($closed !== null): ?>

  <section aria-labelledby="resultado-titulo" class="rounded-2xl border-2 border-ink bg-card p-6 shadow-md">
    <h1 id="resultado-titulo" class="font-display text-3xl font-extrabold tracking-tight">Turno cerrado</h1>
    <p class="mt-1 text-ink/80">
      Turno N.º <?= (int) $closed["id"] ?>: abierto por <?= html((string) $closed["opened_by_name"]) ?>
      el <?= html(Dates::format((string) $closed["opened_at"], "d/m/Y H:i")) ?>
      y cerrado por <?= html((string) ($closed["closed_by_name"] ?? "—")) ?>
      el <?= html(Dates::format($closed["closed_at"], "d/m/Y H:i")) ?>.
    </p>

    <div class="mt-5 rounded-xl border-2 border-ink px-5 py-4 text-center">
      <p class="text-sm font-semibold uppercase tracking-wide">Resultado de la caja</p>
      <p class="mt-1 flex items-center justify-center gap-3 text-4xl font-extrabold tabular-nums">
        <svg class="h-9 w-9 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"
             stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
          <?php if ((int) $closed["difference_cents"] === 0): ?>
            <path d="M5 13l4 4L19 7"/>
          <?php else: ?>
            <path d="M12 3v18"/><path d="M5 8l7-5 7 5"/><path d="M3 14l3-6 3 6a3 3 0 0 1-6 0z"/><path d="M15 14l3-6 3 6a3 3 0 0 1-6 0z"/>
          <?php endif; ?>
        </svg>
        <span><?= html($diffLabel((int) $closed["difference_cents"])) ?></span>
      </p>
    </div>

    <dl class="mt-5 grid gap-3 sm:grid-cols-3">
      <div class="rounded-lg border border-ink/20 p-3">
        <dt class="text-sm text-ink/80">Monto de apertura</dt>
        <dd class="text-lg font-bold tabular-nums"><?= html(Money::formatCents((int) $closed["opening_cents"])) ?></dd>
      </div>
      <div class="rounded-lg border border-ink/20 p-3">
        <dt class="text-sm text-ink/80">Efectivo esperado</dt>
        <dd class="text-lg font-bold tabular-nums"><?= html(Money::formatCents((int) $closed["expected_cents"])) ?></dd>
      </div>
      <div class="rounded-lg border border-ink/20 p-3">
        <dt class="text-sm text-ink/80">Efectivo contado</dt>
        <dd class="text-lg font-bold tabular-nums"><?= html(Money::formatCents((int) $closed["counted_cents"])) ?></dd>
      </div>
    </dl>

    <?php if (!empty($closed["close_note"])): ?>
      <p class="mt-4 text-sm"><span class="font-semibold">Nota:</span> <?= html((string) $closed["close_note"]) ?></p>
    <?php endif; ?>

    <div class="mt-6 flex flex-wrap gap-3">
      <a href="/repositorios/<?= $repoId ?>/caja" class="<?= $btnPrimary ?>">Abrir una nueva caja</a>
      <a href="/repositorios/<?= $repoId ?>/caja/ventas" class="<?= $btnSecondary ?>">Ver historial</a>
      <a href="/dashboard" class="<?= $btnSecondary ?>">Salir</a>
    </div>
  </section>

<?php elseif ($session !== null && $summary !== null): ?>

  <section aria-labelledby="cierre-titulo" class="rounded-2xl border border-ink/20 bg-card p-6 shadow-sm">
    <h1 id="cierre-titulo" class="font-display text-3xl font-extrabold tracking-tight">Cerrar caja</h1>
    <p class="mt-1 text-ink/80">
      Turno N.º <?= (int) $session["id"] ?> abierto por <?= html((string) $session["opened_by_name"]) ?>
      el <?= html(Dates::format((string) $session["opened_at"], "d/m/Y H:i")) ?>.
    </p>

    <?php if ($errors): ?>
      <div role="alert" class="mt-5 rounded-lg border-2 border-danger/60 bg-danger/10 px-4 py-3 text-sm text-danger">
        <p class="font-semibold">Revisa estos datos:</p>
        <ul class="mt-1 list-disc space-y-0.5 pl-5">
          <?php foreach ($errors as $msg): ?>
            <li><?= html((string) $msg) ?></li>
          <?php endforeach; ?>
        </ul>
      </div>
    <?php endif; ?>

    <div class="mt-5 overflow-x-auto rounded-xl border border-ink/20">
      <table class="w-full text-left text-sm">
        <caption class="sr-only">Ventas del turno por método de pago</caption>
        <thead class="bg-ink/10">
          <tr>
            <th scope="col" class="px-4 py-3 font-semibold">Método de pago</th>
            <th scope="col" class="px-4 py-3 text-right font-semibold">Ventas</th>
            <th scope="col" class="px-4 py-3 text-right font-semibold">Total</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-ink/15">
          <?php foreach ($methods as $key => $label): ?>
            <tr>
              <th scope="row" class="px-4 py-3 font-medium"><?= html($label) ?></th>
              <td class="px-4 py-3 text-right tabular-nums"><?= (int) $summary["by_method"][$key]["count"] ?></td>
              <td class="whitespace-nowrap px-4 py-3 text-right tabular-nums"><?= html(Money::formatCents((int) $summary["by_method"][$key]["total_cents"])) ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
        <tfoot class="border-t-2 border-ink/30">
          <tr>
            <th scope="row" class="px-4 py-3 font-bold">Total vendido</th>
            <td class="px-4 py-3 text-right font-bold tabular-nums"><?= (int) $summary["count"] ?></td>
            <td class="whitespace-nowrap px-4 py-3 text-right font-bold tabular-nums"><?= html(Money::formatCents((int) $summary["total_cents"])) ?></td>
          </tr>
        </tfoot>
      </table>
    </div>

    <dl class="mt-5 grid gap-3 sm:grid-cols-2">
      <div class="rounded-lg border border-ink/20 p-3">
        <dt class="text-sm text-ink/80">Monto de apertura</dt>
        <dd class="text-lg font-bold tabular-nums"><?= html(Money::formatCents((int) $session["opening_cents"])) ?></dd>
      </div>
      <div class="rounded-lg border-2 border-ink p-3">
        <dt class="text-sm text-ink/80">Efectivo esperado (apertura + ventas en efectivo)</dt>
        <dd class="text-2xl font-extrabold tabular-nums"><?= html(Money::formatCents($expectedCents)) ?></dd>
      </div>
    </dl>

    <!-- TODO (pendiente): token CSRF en este formulario -->
    <form action="/repositorios/<?= $repoId ?>/caja/cerrar" method="POST" class="mt-6 space-y-5">
      <?php if (is_array($csrf) && isset($csrf["name"], $csrf["value"])): ?>
        <input type="hidden" name="<?= html((string) $csrf["name"]) ?>" value="<?= html((string) $csrf["value"]) ?>">
      <?php endif; ?>
      <input type="hidden" name="session_id" value="<?= (int) $session["id"] ?>">

      <div>
        <label for="counted-cash" class="mb-1.5 block text-sm font-semibold">Efectivo contado</label>
        <input type="text" id="counted-cash" name="counted_cash" required autofocus
               inputmode="decimal" autocomplete="off" placeholder="0,00" aria-describedby="counted-hint"
               value="<?= html((string) ($old["counted_cash"] ?? "")) ?>"
               class="<?= $input ?> tabular-nums">
        <p id="counted-hint" class="mt-1.5 text-sm text-ink/80">
          Cuenta el efectivo que hay en la caja. Coma o punto para los decimales, sin separador de miles.
        </p>
      </div>

      <div>
        <label for="close-note" class="mb-1.5 block text-sm font-semibold">Nota (opcional)</label>
        <input type="text" id="close-note" name="close_note" maxlength="255" autocomplete="off"
               value="<?= html((string) ($old["close_note"] ?? "")) ?>"
               class="<?= $input ?>">
      </div>

      <div class="flex flex-wrap gap-3">
        <button type="submit" class="<?= $btnPrimary ?>">Cerrar turno</button>
        <a href="/repositorios/<?= $repoId ?>/caja" class="<?= $btnSecondary ?>">Volver a la caja</a>
      </div>
    </form>
  </section>

<?php endif; ?>

</div>
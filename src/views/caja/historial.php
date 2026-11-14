<?php
/**
 * src/views/caja/historial.php
 *
 * Historial de ventas + (solo admin) últimos turnos. Se renderiza dentro de layouts/caja.php.
 *
 * Variables:
 *   $repository   array     id, name.
 *   $isAdmin      bool
 *   $sales        array     id, created_at, user_name, session_id, payment_method, discount_type,
 *                           discount_value, discount_cents, subtotal_cents, total_cents, items[].
 *   $total        int       Cantidad de ventas (con filtros).
 *   $page, $pages int
 *   $errors       string[]  Errores de los filtros.
 *   $old          array     desde, hasta (lo que escribió el usuario).
 *   $filterQuery  array     Filtros válidos para los enlaces de paginación.
 *   $sessions     array     Últimos turnos (solo admin).
 */
use App\Models\Dates;
use App\Models\Money;

$repository  = is_array($repository ?? null) ? $repository : [];
$isAdmin     = (bool) ($isAdmin ?? false);
$sales       = is_array($sales ?? null) ? $sales : [];
$total       = (int) ($total ?? 0);
$page        = (int) ($page ?? 1);
$pages       = (int) ($pages ?? 1);
$errors      = is_array($errors ?? null) ? $errors : [];
$old         = is_array($old ?? null) ? $old : [];
$filterQuery = is_array($filterQuery ?? null) ? $filterQuery : [];
$sessions    = is_array($sessions ?? null) ? $sessions : [];
$repoId      = (int) $repository["id"];

$methods = ["efectivo" => "Efectivo", "transferencia" => "Transferencia", "tarjeta" => "Tarjeta"];

$focusRing    = "focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-ink";
$btnPrimary   = "inline-flex min-h-[44px] items-center justify-center rounded-lg bg-accent px-5 py-2 text-sm font-semibold text-onaccent transition-colors hover:bg-accenth " . $focusRing;
$btnSecondary = "inline-flex min-h-[44px] items-center justify-center rounded-lg border border-ink/30 px-5 py-2 text-sm font-semibold text-ink transition-colors hover:bg-ink/10 " . $focusRing;
$input        = "w-full rounded-lg border border-ink/30 bg-card px-3.5 py-2.5 text-base text-ink focus:border-ink focus:outline-none focus:ring-2 focus:ring-ink/30";

$pageUrl = static fn(int $p): string => "/repositorios/" . $repoId . "/caja/ventas?"
  . http_build_query(array_merge($filterQuery, ["pagina" => $p]));

$diffLabel = static function (?int $cents): string {
  if ($cents === null) {
    return "—";
  }

  if ($cents > 0) {
    return "Sobran " . Money::formatCents($cents);
  }

  if ($cents < 0) {
    return "Faltan " . Money::formatCents(abs($cents));
  }

  return "Caja justa";
};

$money = static fn(?int $cents): string => $cents === null ? "—" : Money::formatCents($cents);

// Detalle desplegable de una venta (ítems + descuento)
$details = static function (array $sale) use ($focusRing): string {
  ob_start();
  ?>
  <details>
    <summary class="inline-block cursor-pointer rounded font-semibold underline-offset-2 hover:underline <?= $focusRing ?>">Ver detalle</summary>
    <ul class="mt-2 space-y-1.5 text-sm">
      <?php foreach ($sale["items"] as $item): ?>
        <li>
          <span class="tabular-nums text-ink/80">#<?= (int) $item["code"] ?></span>
          <?= html((string) $item["name"]) ?> —
          <?= (int) $item["quantity"] ?> × <?= html(Money::formatCents((int) $item["unit_cents"])) ?>
          = <strong class="tabular-nums"><?= html(Money::formatCents((int) $item["subtotal_cents"])) ?></strong>
        </li>
      <?php endforeach; ?>
    </ul>
    <p class="mt-2 text-sm">Subtotal: <span class="tabular-nums"><?= html(Money::formatCents((int) $sale["subtotal_cents"])) ?></span></p>
    <p class="text-sm">
      <?php if ($sale["discount_type"] === "percent"): ?>
        Descuento: <?= html(number_format((float) $sale["discount_value"], 2, ",", ".")) ?> %
        (− <?= html(Money::formatCents((int) $sale["discount_cents"])) ?>)
      <?php elseif ($sale["discount_type"] === "amount"): ?>
        Descuento fijo: − <?= html(Money::formatCents((int) $sale["discount_cents"])) ?>
      <?php else: ?>
        Sin descuento
      <?php endif; ?>
    </p>
  </details>
  <?php
  return (string) ob_get_clean();
};
?>
<div class="mx-auto w-full max-w-7xl px-4 py-6">

  <div class="flex flex-wrap items-end justify-between gap-4">
    <div>
      <h1 class="font-display text-2xl font-extrabold tracking-tight">Historial de ventas</h1>
      <p class="mt-1 text-sm text-ink/80">
        <?= $isAdmin ? "Todas las ventas del repositorio." : "Tus ventas en este repositorio." ?>
        <?= $total ?> <?= $total === 1 ? "venta" : "ventas" ?>.
      </p>
    </div>

    <form action="/repositorios/<?= $repoId ?>/caja/ventas" method="GET" class="flex flex-wrap items-end gap-3">
      <div>
        <label for="filtro-desde" class="mb-1 block text-sm font-semibold">Desde</label>
        <input type="date" id="filtro-desde" name="desde" value="<?= html((string) ($old["desde"] ?? "")) ?>" class="<?= $input ?>">
      </div>
      <div>
        <label for="filtro-hasta" class="mb-1 block text-sm font-semibold">Hasta</label>
        <input type="date" id="filtro-hasta" name="hasta" value="<?= html((string) ($old["hasta"] ?? "")) ?>" class="<?= $input ?>">
      </div>
      <button type="submit" class="<?= $btnPrimary ?>">Filtrar</button>
      <?php if ($filterQuery): ?>
        <a href="/repositorios/<?= $repoId ?>/caja/ventas" class="<?= $btnSecondary ?>">Quitar filtros</a>
      <?php endif; ?>
    </form>
  </div>

  <?php if ($errors): ?>
    <div role="alert" class="mt-4 rounded-lg border-2 border-danger/60 bg-danger/10 px-4 py-3 text-sm text-danger">
      <p class="font-semibold">Revisa los filtros:</p>
      <ul class="mt-1 list-disc space-y-0.5 pl-5">
        <?php foreach ($errors as $msg): ?>
          <li><?= html((string) $msg) ?></li>
        <?php endforeach; ?>
      </ul>
    </div>
  <?php endif; ?>

  <?php if (!$sales): ?>
    <p class="mt-6 rounded-2xl border border-dashed border-ink/30 px-6 py-12 text-center text-lg font-medium">
      No hay ventas para mostrar.
    </p>
  <?php else: ?>

    <!-- Tabla (pantallas medianas y grandes) -->
    <div class="mt-6 hidden overflow-x-auto rounded-xl border border-ink/20 bg-card md:block">
      <table class="w-full text-left text-sm">
        <caption class="sr-only">Ventas del repositorio <?= html((string) $repository["name"]) ?>, de la más reciente a la más antigua</caption>
        <thead class="bg-ink/10">
          <tr>
            <th scope="col" class="px-4 py-3 font-semibold">N.º</th>
            <th scope="col" class="px-4 py-3 font-semibold">Fecha y hora</th>
            <th scope="col" class="px-4 py-3 font-semibold">Operador</th>
            <th scope="col" class="px-4 py-3 font-semibold">Turno</th>
            <th scope="col" class="px-4 py-3 font-semibold">Pago</th>
            <th scope="col" class="px-4 py-3 text-right font-semibold">Total</th>
            <th scope="col" class="px-4 py-3 font-semibold">Detalle</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-ink/15 align-top">
          <?php foreach ($sales as $sale): ?>
            <tr>
              <th scope="row" class="px-4 py-3 font-semibold tabular-nums"><?= (int) $sale["id"] ?></th>
              <td class="whitespace-nowrap px-4 py-3 tabular-nums"><?= html(Dates::format((string) $sale["created_at"])) ?></td>
              <td class="px-4 py-3"><?= html((string) $sale["user_name"]) ?></td>
              <td class="px-4 py-3 tabular-nums"><?= $sale["session_id"] === null ? "—" : (int) $sale["session_id"] ?></td>
              <td class="px-4 py-3"><?= html($methods[(string) $sale["payment_method"]] ?? (string) $sale["payment_method"]) ?></td>
              <td class="whitespace-nowrap px-4 py-3 text-right font-semibold tabular-nums"><?= html(Money::formatCents((int) $sale["total_cents"])) ?></td>
              <td class="px-4 py-3"><?= $details($sale) ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>

    <!-- Tarjetas (móvil) -->
    <ul class="mt-6 space-y-3 md:hidden" aria-label="Ventas del repositorio <?= html((string) $repository["name"]) ?>">
      <?php foreach ($sales as $sale): ?>
        <li class="rounded-xl border border-ink/20 bg-card p-4">
          <div class="flex flex-wrap items-baseline justify-between gap-2">
            <h2 class="font-semibold">Venta N.º <?= (int) $sale["id"] ?></h2>
            <p class="text-lg font-bold tabular-nums"><?= html(Money::formatCents((int) $sale["total_cents"])) ?></p>
          </div>
          <dl class="mt-2 grid grid-cols-2 gap-2 text-sm">
            <div class="col-span-2">
              <dt class="text-ink/80">Fecha y hora</dt>
              <dd class="tabular-nums"><?= html(Dates::format((string) $sale["created_at"])) ?></dd>
            </div>
            <div>
              <dt class="text-ink/80">Operador</dt>
              <dd><?= html((string) $sale["user_name"]) ?></dd>
            </div>
            <div>
              <dt class="text-ink/80">Turno</dt>
              <dd class="tabular-nums"><?= $sale["session_id"] === null ? "—" : (int) $sale["session_id"] ?></dd>
            </div>
            <div>
              <dt class="text-ink/80">Pago</dt>
              <dd><?= html($methods[(string) $sale["payment_method"]] ?? (string) $sale["payment_method"]) ?></dd>
            </div>
          </dl>
          <div class="mt-3 border-t border-ink/15 pt-3"><?= $details($sale) ?></div>
        </li>
      <?php endforeach; ?>
    </ul>

    <?php if ($pages > 1): ?>
      <nav aria-label="Paginación" class="mt-6 flex flex-wrap items-center justify-between gap-3">
        <p class="text-sm">Página <?= $page ?> de <?= $pages ?></p>
        <div class="flex gap-2">
          <?php if ($page > 1): ?>
            <a href="<?= html($pageUrl($page - 1)) ?>" class="<?= $btnSecondary ?>"><span aria-hidden="true">←</span>&nbsp;Anterior</a>
          <?php endif; ?>
          <?php if ($page < $pages): ?>
            <a href="<?= html($pageUrl($page + 1)) ?>" class="<?= $btnSecondary ?>">Siguiente&nbsp;<span aria-hidden="true">→</span></a>
          <?php endif; ?>
        </div>
      </nav>
    <?php endif; ?>
  <?php endif; ?>

  <?php if ($isAdmin): ?>
    <section aria-labelledby="turnos-titulo" class="mt-10">
      <h2 id="turnos-titulo" class="font-display text-xl font-extrabold tracking-tight">Turnos (últimos 10)</h2>

      <?php if (!$sessions): ?>
        <p class="mt-3 rounded-2xl border border-dashed border-ink/30 px-6 py-8 text-center font-medium">Todavía no hay turnos.</p>
      <?php else: ?>
        <div class="mt-3 overflow-x-auto rounded-xl border border-ink/20 bg-card">
          <table class="w-full text-left text-sm">
            <caption class="sr-only">Últimos turnos de caja del repositorio <?= html((string) $repository["name"]) ?></caption>
            <thead class="bg-ink/10">
              <tr>
                <th scope="col" class="px-4 py-3 font-semibold">Turno</th>
                <th scope="col" class="px-4 py-3 font-semibold">Apertura</th>
                <th scope="col" class="px-4 py-3 font-semibold">Cierre</th>
                <th scope="col" class="px-4 py-3 font-semibold">Abrió</th>
                <th scope="col" class="px-4 py-3 font-semibold">Cerró</th>
                <th scope="col" class="px-4 py-3 text-right font-semibold">Monto de apertura</th>
                <th scope="col" class="px-4 py-3 text-right font-semibold">Efectivo esperado</th>
                <th scope="col" class="px-4 py-3 text-right font-semibold">Efectivo contado</th>
                <th scope="col" class="px-4 py-3 font-semibold">Diferencia</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-ink/15">
              <?php foreach ($sessions as $turn): ?>
                <tr>
                  <th scope="row" class="px-4 py-3 font-semibold tabular-nums"><?= (int) $turn["id"] ?></th>
                  <td class="whitespace-nowrap px-4 py-3 tabular-nums"><?= html(Dates::format((string) $turn["opened_at"], "d/m/Y H:i")) ?></td>
                  <td class="whitespace-nowrap px-4 py-3 tabular-nums">
                    <?= $turn["status"] === "open" ? "Abierto" : html(Dates::format($turn["closed_at"], "d/m/Y H:i")) ?>
                  </td>
                  <td class="px-4 py-3"><?= html((string) $turn["opened_by_name"]) ?></td>
                  <td class="px-4 py-3"><?= html((string) ($turn["closed_by_name"] ?? "—")) ?></td>
                  <td class="whitespace-nowrap px-4 py-3 text-right tabular-nums"><?= html($money((int) $turn["opening_cents"])) ?></td>
                  <td class="whitespace-nowrap px-4 py-3 text-right tabular-nums"><?= html($money($turn["expected_cents"])) ?></td>
                  <td class="whitespace-nowrap px-4 py-3 text-right tabular-nums"><?= html($money($turn["counted_cents"])) ?></td>
                  <td class="whitespace-nowrap px-4 py-3 font-semibold tabular-nums"><?= html($diffLabel($turn["difference_cents"])) ?></td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php endif; ?>
    </section>
  <?php endif; ?>
</div>
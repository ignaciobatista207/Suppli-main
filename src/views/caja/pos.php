<?php
/**
 * src/views/caja/pos.php
 *
 * Pantalla de venta. Se renderiza dentro de layouts/caja.php (sin columna lateral).
 * El carrito vive en JS (public/js/caja.js) y se envía como campos ocultos
 * items[i][product_id] e items[i][quantity]. El servidor recalcula TODO.
 *
 * Variables:
 *   $repository  array       id, name.
 *   $errors      string[]    Errores de la venta (422).
 *   $old         array       discount_type, discount_value, payment_method, amount_received.
 *   $cart        array       Carrito reconstruido con datos frescos (id, code, name, price_cents, stock, quantity).
 *   $receipt     array|null  Venta recién confirmada: id, total_cents, payment_method, received_cents, change_cents.
 *   $saleToken   string      Token de un solo uso de esta pantalla.
 *   $csrf        array|null
 */
use App\Models\Money;

$repository = is_array($repository ?? null) ? $repository : [];
$errors     = is_array($errors ?? null) ? $errors : [];
$old        = is_array($old ?? null) ? $old : [];
$cart       = is_array($cart ?? null) ? $cart : [];
$receipt    = is_array($receipt ?? null) ? $receipt : null;
$saleToken  = (string) ($saleToken ?? "");
$csrf       = $csrf ?? null;
$repoId     = (int) $repository["id"];

$methods = ["efectivo" => "Efectivo", "transferencia" => "Transferencia", "tarjeta" => "Tarjeta"];

$typeOld        = (string) ($old["discount_type"] ?? "none");
$discountType   = in_array($typeOld, ["none", "percent", "amount"], true) ? $typeOld : "none";
$methodOld      = (string) ($old["payment_method"] ?? "efectivo");
$paymentMethod  = isset($methods[$methodOld]) ? $methodOld : "efectivo";

$initialCart = json_encode(
  $cart,
  JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR,
);

$focusRing  = "focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-ink";
$btnPrimary = "inline-flex min-h-[56px] items-center justify-center gap-2 rounded-xl bg-accent px-6 py-3 text-lg font-bold text-onaccent transition-colors hover:bg-accenth disabled:cursor-not-allowed disabled:opacity-60 " . $focusRing;
$btnSecondary = "inline-flex min-h-[48px] items-center justify-center rounded-lg border border-ink/30 px-5 py-2.5 text-sm font-semibold text-ink transition-colors hover:bg-ink/10 disabled:cursor-not-allowed disabled:opacity-60 " . $focusRing;
$input      = "w-full rounded-lg border border-ink/30 bg-card px-3.5 py-2.5 text-base text-ink focus:border-ink focus:outline-none focus:ring-2 focus:ring-ink/30";
$stepBtn    = "inline-flex h-12 w-12 shrink-0 items-center justify-center rounded-lg border border-ink/30 text-xl font-bold text-ink transition-colors hover:bg-ink/10 " . $focusRing;
?>
<div id="caja-app" data-search-url="/repositorios/<?= $repoId ?>/caja/productos"
     class="mx-auto w-full max-w-7xl px-4 py-4 pb-44 lg:pb-8">

  <?php if ($receipt !== null): ?>
    <section id="sale-receipt" role="status" aria-labelledby="receipt-title"
             class="mb-6 rounded-2xl border-2 border-ink bg-card p-6 shadow-md">
      <h2 id="receipt-title" class="font-display text-2xl font-extrabold tracking-tight">
        Venta N.º <?= (int) $receipt["id"] ?> registrada
      </h2>

      <dl class="mt-4 grid gap-4 sm:grid-cols-2">
        <div>
          <dt class="text-sm text-ink/80">Total cobrado</dt>
          <dd class="text-3xl font-extrabold tabular-nums"><?= html(Money::formatCents((int) $receipt["total_cents"])) ?></dd>
        </div>
        <div>
          <dt class="text-sm text-ink/80">Método de pago</dt>
          <dd class="text-xl font-semibold"><?= html($methods[(string) $receipt["payment_method"]] ?? (string) $receipt["payment_method"]) ?></dd>
        </div>
      </dl>

      <?php if ($receipt["payment_method"] === "efectivo" && $receipt["change_cents"] !== null): ?>
        <div class="mt-5 rounded-xl border-2 border-ink px-5 py-4 text-center">
          <p class="text-sm font-semibold uppercase tracking-wide">Vuelto a entregar</p>
          <p class="text-5xl font-extrabold tabular-nums sm:text-6xl"><?= html(Money::formatCents((int) $receipt["change_cents"])) ?></p>
          <?php if ($receipt["received_cents"] !== null): ?>
            <p class="mt-1 text-sm text-ink/80">Recibido: <?= html(Money::formatCents((int) $receipt["received_cents"])) ?></p>
          <?php endif; ?>
        </div>
      <?php endif; ?>

      <button type="button" data-new-sale class="<?= $btnPrimary ?> mt-5 w-full sm:w-auto">Nueva venta</button>
    </section>
  <?php endif; ?>

  <?php if ($errors): ?>
    <div role="alert" class="mb-6 rounded-lg border-2 border-danger/60 bg-danger/10 px-4 py-3 text-sm text-danger">
      <p class="font-semibold">No se pudo confirmar la venta:</p>
      <ul class="mt-1 list-disc space-y-0.5 pl-5">
        <?php foreach ($errors as $msg): ?>
          <li><?= html((string) $msg) ?></li>
        <?php endforeach; ?>
      </ul>
    </div>
  <?php endif; ?>

  <!-- TODO (pendiente): token CSRF en este formulario (ahora hay dinero de por medio) -->
  <form id="sale-form" action="/repositorios/<?= $repoId ?>/caja/ventas" method="POST" autocomplete="off" novalidate>
    <?php if (is_array($csrf) && isset($csrf["name"], $csrf["value"])): ?>
      <input type="hidden" name="<?= html((string) $csrf["name"]) ?>" value="<?= html((string) $csrf["value"]) ?>">
    <?php endif; ?>
    <input type="hidden" name="sale_token" value="<?= html($saleToken) ?>">
    <div id="cart-fields"></div>

    <div class="grid gap-6 lg:grid-cols-[minmax(0,3fr)_minmax(0,2fr)] lg:items-start">

      <!-- IZQUIERDA: buscador + ítems -->
      <section aria-labelledby="venta-titulo">
        <h1 id="venta-titulo" class="sr-only">Nueva venta</h1>

        <div class="relative">
          <label for="caja-search" class="mb-1.5 block text-sm font-semibold">Buscar producto por nombre o por #código</label>
          <input type="search" id="caja-search" autofocus autocomplete="off" maxlength="50"
                 role="combobox" aria-expanded="false" aria-controls="caja-results" aria-autocomplete="list"
                 placeholder="Ej.: arroz, o 12 para el #12"
                 class="<?= $input ?> h-14 text-lg">
          <ul id="caja-results" role="listbox" aria-label="Resultados de la búsqueda" hidden
              class="absolute z-20 mt-1 max-h-80 w-full overflow-y-auto rounded-xl border border-ink/30 bg-card shadow-lg"></ul>
        </div>
        <p id="caja-status" role="status" aria-live="polite" class="mt-2 min-h-[1.25rem] text-sm font-medium"></p>

        <p id="cart-empty" class="mt-2 rounded-2xl border border-dashed border-ink/30 px-6 py-12 text-center text-lg font-medium">
          Todavía no agregaste productos. Búscalos arriba y pulsa Enter para agregar el primero.
        </p>
        <ul id="cart-list" aria-label="Productos de la venta" class="mt-2 space-y-3"></ul>
      </section>

      <!-- DERECHA: resumen, descuento, pago -->
      <aside aria-labelledby="resumen-titulo"
             class="rounded-2xl border border-ink/20 bg-card p-5 shadow-sm lg:sticky lg:top-4">
        <h2 id="resumen-titulo" class="font-display text-xl font-extrabold tracking-tight">Resumen</h2>

        <dl class="mt-4 space-y-2 text-base">
          <div class="flex items-baseline justify-between gap-3">
            <dt>Subtotal</dt>
            <dd class="font-semibold tabular-nums" data-subtotal>$ 0,00</dd>
          </div>
          <div class="flex items-baseline justify-between gap-3">
            <dt>Descuento</dt>
            <dd class="font-semibold tabular-nums" data-discount>− $ 0,00</dd>
          </div>
        </dl>

        <fieldset class="mt-4">
          <legend class="mb-2 text-sm font-semibold">Descuento a la venta</legend>
          <div class="grid grid-cols-2 gap-2">
            <div>
              <label for="discount-type" class="sr-only">Tipo de descuento</label>
              <select id="discount-type" name="discount_type" class="<?= $input ?> h-12">
                <option value="none" <?= $discountType === "none" ? "selected" : "" ?>>Sin descuento</option>
                <option value="percent" <?= $discountType === "percent" ? "selected" : "" ?>>Porcentaje (%)</option>
                <option value="amount" <?= $discountType === "amount" ? "selected" : "" ?>>Monto fijo ($)</option>
              </select>
            </div>
            <div id="discount-wrap" <?= $discountType === "none" ? "hidden" : "" ?>>
              <label for="discount-value" id="discount-label" class="sr-only">Valor del descuento</label>
              <input type="text" id="discount-value" name="discount_value" inputmode="decimal"
                     autocomplete="off" placeholder="0" aria-describedby="discount-hint"
                     value="<?= html((string) ($old["discount_value"] ?? "")) ?>"
                     class="<?= $input ?> h-12 tabular-nums" <?= $discountType === "none" ? "disabled" : "" ?>>
            </div>
          </div>
          <p id="discount-hint" class="mt-1.5 text-sm text-ink/80"></p>
        </fieldset>

        <div class="mt-5 hidden border-t border-ink/15 pt-4 lg:block">
          <p class="text-sm font-semibold uppercase tracking-wide">Total</p>
          <p class="text-5xl font-extrabold tabular-nums" data-total>$ 0,00</p>
        </div>

        <fieldset class="mt-5">
          <legend class="mb-2 text-sm font-semibold">Método de pago</legend>
          <div class="grid grid-cols-3 gap-2">
            <?php foreach ($methods as $value => $label): ?>
              <div>
                <input type="radio" id="pay-<?= $value ?>" name="payment_method" value="<?= $value ?>"
                       class="peer sr-only" <?= $value === $paymentMethod ? "checked" : "" ?>>
                <label for="pay-<?= $value ?>"
                       class="flex min-h-[60px] cursor-pointer items-center justify-center rounded-xl border-2 border-ink/30 px-2 text-center text-sm font-semibold transition-colors hover:bg-ink/10 peer-checked:border-ink peer-checked:bg-accent peer-checked:text-onaccent peer-focus-visible:outline peer-focus-visible:outline-2 peer-focus-visible:outline-offset-2 peer-focus-visible:outline-ink">
                  <?= html($label) ?>
                </label>
              </div>
            <?php endforeach; ?>
          </div>
        </fieldset>

        <div id="cash-block" class="mt-4 space-y-2">
          <label for="amount-received" class="block text-sm font-semibold">Monto recibido</label>
          <input type="text" id="amount-received" name="amount_received" inputmode="decimal"
                 autocomplete="off" placeholder="0,00"
                 value="<?= html((string) ($old["amount_received"] ?? "")) ?>"
                 class="<?= $input ?> h-14 text-xl tabular-nums">
          <p id="change-line" role="status" aria-live="polite"
             class="rounded-lg border-2 border-ink px-3 py-2 text-lg font-bold tabular-nums"></p>
        </div>

        <p data-hint class="mt-4 hidden text-sm font-medium lg:block"></p>

        <div class="mt-4 hidden gap-3 lg:grid">
          <button type="submit" data-confirm disabled class="<?= $btnPrimary ?> w-full">
            <span data-confirm-label>Confirmar venta</span>
          </button>
          <button type="button" id="clear-cart" class="<?= $btnSecondary ?> w-full">Vaciar</button>
        </div>

        <div class="mt-4 lg:hidden">
          <button type="button" data-clear-mobile class="<?= $btnSecondary ?> w-full">Vaciar</button>
        </div>
      </aside>
    </div>

    <!-- BARRA FIJA INFERIOR (< lg): total y confirmar -->
    <div class="fixed inset-x-0 bottom-0 z-30 border-t border-ink/20 bg-card px-4 pt-3 shadow-[0_-4px_12px_rgba(0,0,0,0.12)] lg:hidden"
         style="padding-bottom: calc(0.75rem + env(safe-area-inset-bottom, 0px))">
      <div class="mx-auto flex max-w-7xl items-center gap-3">
        <div class="min-w-0 flex-1">
          <p class="text-xs font-semibold uppercase tracking-wide">Total</p>
          <p class="truncate text-2xl font-extrabold tabular-nums" data-total>$ 0,00</p>
        </div>
        <button type="submit" data-confirm disabled class="<?= $btnPrimary ?> shrink-0">
          <span data-confirm-label>Confirmar venta</span>
        </button>
      </div>
      <p data-hint class="mx-auto mt-1 max-w-7xl text-xs font-medium"></p>
    </div>
  </form>

  <!-- Plantilla de un ítem del carrito (la clona caja.js; todo el texto se asigna con textContent) -->
  <template id="cart-item-template">
    <li class="rounded-xl border border-ink/20 bg-card p-4">
      <div class="flex flex-wrap items-start justify-between gap-3">
        <div class="min-w-0">
          <p class="break-words text-lg font-semibold" data-name></p>
          <p class="text-sm text-ink/80"><span class="tabular-nums" data-code></span> · <span class="tabular-nums" data-unit></span> c/u</p>
        </div>
        <button type="button" data-remove
                class="inline-flex min-h-[44px] items-center rounded-lg border border-ink/30 px-4 text-sm font-semibold text-ink transition-colors hover:bg-ink/10 <?= $focusRing ?>">
          Quitar
        </button>
      </div>
      <div class="mt-3 flex flex-wrap items-center justify-between gap-3">
        <div class="flex items-center gap-2">
          <button type="button" data-minus class="<?= $stepBtn ?>"><span aria-hidden="true">&minus;</span></button>
          <input type="number" data-qty min="1" step="1" inputmode="numeric"
                 class="h-12 w-20 rounded-lg border border-ink/30 bg-card px-2 text-center text-lg tabular-nums text-ink focus:border-ink focus:outline-none focus:ring-2 focus:ring-ink/30">
          <button type="button" data-plus class="<?= $stepBtn ?>"><span aria-hidden="true">+</span></button>
        </div>
        <p class="text-xl font-bold tabular-nums" data-subtotal></p>
      </div>
      <p class="mt-2 text-sm font-semibold" role="alert" data-warn hidden></p>
    </li>
  </template>

  <script type="application/json" id="cart-init"><?= $initialCart ?></script>
</div>

<script src="/js/caja.js" defer></script>
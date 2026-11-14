<?php
/**
 * src/views/gondolas/show.php
 *
 * Página de una góndola con sus productos. Se renderiza dentro de layouts/app.php.
 * Usa solo los tokens de color del tema; el único color "fijo" es el de la góndola (dato del usuario).
 *
 * Variables:
 *   $repository       array   id, name, role.
 *   $gondola          array   id, name, color, category_name, parent_category_name.
 *   $isAdmin          bool
 *   $products         array   id, code, name, price, stock, min_stock, is_low.
 *   $categoryOptions  array   [{id, name}] posibles categorías del producto (solo admin).
 *   $errors           string[]  Errores de la viñeta "Agregar producto" (la abren automáticamente).
 *   $stockErrors      string[]  Errores de la edición manual de stock (se muestran sobre la lista).
 *   $old              array   Valores previos del formulario.
 *   $openDialog       bool
 *   $csrf             array|null  ["name" => "...", "value" => "..."] si el proyecto usa CSRF.
 */
use App\Models\Money;
use App\Models\Palette;

$repository      = is_array($repository ?? null) ? $repository : [];
$gondola         = is_array($gondola ?? null) ? $gondola : [];
$isAdmin         = (bool) ($isAdmin ?? false);
$products        = is_array($products ?? null) ? $products : [];
$categoryOptions = is_array($categoryOptions ?? null) ? $categoryOptions : [];
$errors          = is_array($errors ?? null) ? $errors : [];
$stockErrors     = is_array($stockErrors ?? null) ? $stockErrors : [];
$old             = is_array($old ?? null) ? $old : [];
$openDialog      = (bool) ($openDialog ?? false);
$csrf            = $csrf ?? null;
$img             = "/images-suppli/";

$repoId     = (int) $repository["id"];
$gondolaId  = (int) $gondola["id"];
$categoryLabel = $gondola["parent_category_name"] !== null
  ? html((string) $gondola["parent_category_name"]) . " › " . html((string) $gondola["category_name"])
  : html((string) $gondola["category_name"]);

$focusRing    = "focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-ink";
$btnPrimary   = "inline-flex items-center justify-center gap-2 rounded-lg bg-accent px-5 py-2.5 text-sm font-semibold text-onaccent transition-colors hover:bg-accenth " . $focusRing;
$btnSecondary = "rounded-lg border border-ink/30 px-5 py-2.5 text-sm font-semibold text-ink transition-colors hover:bg-ink/10 " . $focusRing;
$input        = "w-full rounded-lg border border-ink/30 bg-card px-3.5 py-2.5 text-base text-ink focus:border-ink focus:outline-none focus:ring-2 focus:ring-ink/30";
$dialogClass  = "m-auto w-[calc(100%-2rem)] max-w-md rounded-2xl bg-card p-0 text-ink shadow-2xl backdrop:bg-black/60";
$closeClass   = "-mr-2 -mt-2 rounded-lg px-3 py-1 text-2xl leading-none text-ink hover:bg-ink/10 " . $focusRing;
$linkClass    = "rounded underline-offset-2 hover:underline " . $focusRing;

// Etiqueta "Stock bajo": texto + borde grueso + ícono (no depende solo del color, sin rojo)
$lowBadge = '<span class="inline-flex items-center gap-1 rounded-full border-2 border-ink px-2 py-0.5 text-xs font-bold">'
  . '<svg class="h-3.5 w-3.5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" '
  . 'stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">'
  . '<path d="M12 9v4"/><path d="M12 17h.01"/>'
  . '<path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/>'
  . '</svg>Stock bajo</span>';

// Control de stock de un producto.
//  - Miembro: solo el número.
//  - Admin: un <form> con − / input numérico / + / Guardar. Funciona sin JS (se escribe el número
//    y se guarda); los botones − y + nacen ocultos y los muestra el script del final.
$stockControl = function (array $product) use ($isAdmin, $repoId, $gondolaId, $csrf, $focusRing): string {
  $stock = (int) $product["stock"];

  if (!$isAdmin) {
    return '<span class="tabular-nums">' . $stock . '</span>';
  }

  $name   = html((string) $product["name"]);
  $action = "/repositorios/" . $repoId . "/gondolas/" . $gondolaId
    . "/productos/" . (int) $product["id"] . "/stock";
  $step   = "hidden h-10 w-10 shrink-0 items-center justify-center rounded-lg border border-ink/30 "
    . "text-lg font-bold text-ink transition-colors hover:bg-ink/10 " . $focusRing;
  $field  = "h-10 w-24 rounded-lg border border-ink/30 bg-card px-2 text-center text-base tabular-nums text-ink "
    . "focus:border-ink focus:outline-none focus:ring-2 focus:ring-ink/30";
  $save   = "h-10 rounded-lg bg-accent px-4 text-sm font-semibold text-onaccent transition-colors hover:bg-accenth "
    . $focusRing;

  ob_start();
  ?>
  <form action="<?= html($action) ?>" method="POST" data-stock-form
        class="flex flex-wrap items-center justify-end gap-2">
    <?php if (is_array($csrf) && isset($csrf["name"], $csrf["value"])): ?>
      <input type="hidden" name="<?= html((string) $csrf["name"]) ?>" value="<?= html((string) $csrf["value"]) ?>">
    <?php endif; ?>
    <input type="hidden" name="expected_stock" value="<?= $stock ?>">

    <button type="button" data-stock-step="-1" aria-label="Restar 1 al stock de <?= $name ?>" class="<?= $step ?>">
      <span aria-hidden="true">&minus;</span>
    </button>
    <input type="number" name="new_stock" value="<?= $stock ?>" min="0" max="9999999" step="1"
           inputmode="numeric" required aria-label="Stock de <?= $name ?>" class="<?= $field ?>">
    <button type="button" data-stock-step="1" aria-label="Sumar 1 al stock de <?= $name ?>" class="<?= $step ?>">
      <span aria-hidden="true">+</span>
    </button>
    <button type="submit" aria-label="Guardar stock de <?= $name ?>" class="<?= $save ?>">Guardar</button>
  </form>
  <?php
  return (string) ob_get_clean();
};

// Categoría preseleccionada en el select (la de la góndola si no hay valor previo válido)
$selectedCategory = (int) ($old["category_id"] ?? 0);
$optionIds = array_column($categoryOptions, "id");
if (!in_array($selectedCategory, $optionIds, true)) {
  $selectedCategory = (int) ($optionIds[0] ?? 0);
}
?>
<div class="mx-auto w-full max-w-5xl p-6">

  <nav aria-label="Migas de pan" class="text-sm">
    <ol class="flex flex-wrap items-center gap-x-2 gap-y-1">
      <li><a href="/dashboard" class="<?= $linkClass ?>">Inicio</a></li>
      <li aria-hidden="true">›</li>
      <li><a href="/repositorios/<?= $repoId ?>" class="<?= $linkClass ?>"><?= html((string) $repository["name"]) ?></a></li>
      <li aria-hidden="true">›</li>
      <li aria-current="page" class="font-semibold"><?= html((string) $gondola["name"]) ?></li>
    </ol>
  </nav>

  <div class="mt-4 overflow-hidden rounded-xl border border-ink/20 bg-card shadow-sm">
    <div class="h-2.5" style="background-color: <?= html(Palette::safe((string) $gondola["color"])) ?>" aria-hidden="true"></div>
    <div class="flex flex-wrap items-center justify-between gap-4 p-4 sm:p-5">
      <div class="min-w-0">
        <h1 class="break-words font-display text-2xl font-extrabold tracking-tight">
          <?= html((string) $gondola["name"]) ?>
        </h1>
        <p class="mt-1 text-sm text-ink/80"><?= $categoryLabel ?></p>
      </div>

      <?php if ($isAdmin): ?>
        <button type="button" data-dialog-open="product-dialog" aria-haspopup="dialog" class="<?= $btnPrimary ?>">
          <img src="<?= $img ?>botonMas.png" alt="" width="20" height="20" class="icon-on-accent h-5 w-5 shrink-0">
          <span>Agregar producto</span>
        </button>
      <?php endif; ?>
    </div>
  </div>

  <?php if ($stockErrors): ?>
    <div role="alert" class="mt-6 rounded-lg border-2 border-danger/60 bg-danger/10 px-4 py-3 text-sm text-danger">
      <p class="font-semibold">No se pudo actualizar el stock:</p>
      <ul class="mt-1 list-disc space-y-0.5 pl-5">
        <?php foreach ($stockErrors as $msg): ?>
          <li><?= html((string) $msg) ?></li>
        <?php endforeach; ?>
      </ul>
    </div>
  <?php endif; ?>

  <!-- TODO: editar producto (nombre, precio) -->
  <?php if (!$products): ?>
    <p class="mt-8 rounded-2xl border border-dashed border-ink/30 px-6 py-12 text-center text-lg font-medium">
      Esta góndola todavía no tiene productos.
    </p>
  <?php else: ?>

    <!-- Tabla (pantallas grandes) -->
    <div class="mt-6 hidden overflow-x-auto rounded-xl border border-ink/20 bg-card md:block">
      <table class="w-full text-left text-sm">
        <caption class="sr-only">Productos de la góndola <?= html((string) $gondola["name"]) ?></caption>
        <thead class="bg-ink/10">
          <tr>
            <th scope="col" class="px-4 py-3 font-semibold">N.º</th>
            <th scope="col" class="px-4 py-3 font-semibold">Producto</th>
            <th scope="col" class="px-4 py-3 text-right font-semibold">Precio</th>
            <th scope="col" class="px-4 py-3 text-right font-semibold">Stock</th>
            <th scope="col" class="px-4 py-3 text-right font-semibold">Stock mínimo</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-ink/15">
          <?php foreach ($products as $product): ?>
            <tr>
              <td class="whitespace-nowrap px-4 py-3 tabular-nums text-ink/80">#<?= (int) $product["code"] ?></td>
              <th scope="row" class="px-4 py-3 font-medium"><?= html((string) $product["name"]) ?></th>
              <td class="whitespace-nowrap px-4 py-3 text-right tabular-nums"><?= html(Money::format((string) $product["price"])) ?></td>
              <td class="px-4 py-3">
                <div class="flex items-center justify-end gap-3">
                  <?php if ($product["is_low"]): ?><?= $lowBadge ?><?php endif; ?>
                  <?= $stockControl($product) ?>
                </div>
              </td>
              <td class="px-4 py-3 text-right tabular-nums">
                <?= (int) $product["min_stock"] > 0 ? (int) $product["min_stock"] : "Sin alerta" ?>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>

    <!-- Tarjetas (móvil) -->
    <ul class="mt-6 space-y-3 md:hidden" aria-label="Productos de la góndola <?= html((string) $gondola["name"]) ?>">
      <?php foreach ($products as $product): ?>
        <li class="rounded-xl border border-ink/20 bg-card p-4">
          <div class="flex flex-wrap items-center justify-between gap-2">
            <h2 class="min-w-0 break-words font-semibold">
              <span class="font-normal tabular-nums text-ink/80">#<?= (int) $product["code"] ?></span>
              <?= html((string) $product["name"]) ?>
            </h2>
            <?php if ($product["is_low"]): ?><?= $lowBadge ?><?php endif; ?>
          </div>
          <dl class="mt-3 grid grid-cols-3 gap-2 text-sm">
            <div>
              <dt class="text-ink/80">Precio</dt>
              <dd class="font-medium tabular-nums"><?= html(Money::format((string) $product["price"])) ?></dd>
            </div>
            <div>
              <dt class="text-ink/80">Stock</dt>
              <dd class="font-medium tabular-nums"><?= (int) $product["stock"] ?></dd>
            </div>
            <div>
              <dt class="text-ink/80">Mínimo</dt>
              <dd class="font-medium tabular-nums"><?= (int) $product["min_stock"] > 0 ? (int) $product["min_stock"] : "Sin alerta" ?></dd>
            </div>
          </dl>
          <?php if ($isAdmin): ?>
            <div class="mt-3 border-t border-ink/15 pt-3">
              <p class="mb-2 text-sm font-semibold">Editar stock</p>
              <?= $stockControl($product) ?>
            </div>
          <?php endif; ?>
        </li>
      <?php endforeach; ?>
    </ul>
  <?php endif; ?>
</div>

<?php if ($isAdmin): ?>

  <!-- VIÑETA: AGREGAR PRODUCTO -->
  <dialog id="product-dialog" data-dialog aria-labelledby="product-dialog-title"
          data-autoopen="<?= ($errors && $openDialog) ? "1" : "0" ?>"
          class="<?= $dialogClass ?>">
    <div class="max-h-[calc(100dvh-2rem)] overflow-y-auto p-6">
      <div class="flex items-start justify-between gap-4">
        <h2 id="product-dialog-title" class="font-display text-2xl font-extrabold tracking-tight">Agregar producto</h2>
        <button type="button" data-close aria-label="Cerrar" class="<?= $closeClass ?>">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>

      <?php if ($errors && $openDialog): ?>
        <div role="alert" class="mt-4 rounded-lg border border-danger/40 bg-danger/10 px-4 py-3 text-sm text-danger">
          <p class="font-semibold">Revisa estos datos:</p>
          <ul class="mt-1 list-disc space-y-0.5 pl-5">
            <?php foreach ($errors as $msg): ?>
              <li><?= html((string) $msg) ?></li>
            <?php endforeach; ?>
          </ul>
        </div>
      <?php endif; ?>

      <!-- TODO (pendiente): token CSRF en este formulario -->
      <form action="/repositorios/<?= $repoId ?>/gondolas/<?= $gondolaId ?>/productos" method="POST" class="mt-5 space-y-4">
        <?php if (is_array($csrf) && isset($csrf["name"], $csrf["value"])): ?>
          <input type="hidden" name="<?= html((string) $csrf["name"]) ?>" value="<?= html((string) $csrf["value"]) ?>">
        <?php endif; ?>

        <div>
          <label for="product-name" class="mb-1.5 block text-sm font-semibold">Nombre del producto</label>
          <input type="text" id="product-name" name="name" required maxlength="150" autocomplete="off" data-autofocus
                 value="<?= html((string) ($old["name"] ?? "")) ?>"
                 class="<?= $input ?>">
        </div>

        <div>
          <label for="product-price" class="mb-1.5 block text-sm font-semibold">Precio de venta</label>
          <input type="text" id="product-price" name="price" required inputmode="decimal" autocomplete="off"
                 placeholder="1234,50" aria-describedby="product-price-hint"
                 value="<?= html((string) ($old["price"] ?? "")) ?>"
                 class="<?= $input ?>">
          <p id="product-price-hint" class="mt-1.5 text-sm text-ink/80">Usa coma o punto para los decimales. Sin separador de miles.</p>
        </div>

        <div>
          <label for="product-stock" class="mb-1.5 block text-sm font-semibold">Stock actual</label>
          <input type="text" id="product-stock" name="stock" required inputmode="numeric" pattern="[0-9]*" autocomplete="off"
                 value="<?= html((string) ($old["stock"] ?? "0")) ?>"
                 class="<?= $input ?>">
        </div>

        <div>
          <label for="product-min-stock" class="mb-1.5 block text-sm font-semibold">Stock mínimo sugerido</label>
          <input type="text" id="product-min-stock" name="min_stock" required inputmode="numeric" pattern="[0-9]*" autocomplete="off"
                 aria-describedby="product-min-stock-hint"
                 value="<?= html((string) ($old["min_stock"] ?? "0")) ?>"
                 class="<?= $input ?>">
          <p id="product-min-stock-hint" class="mt-1.5 text-sm text-ink/80">
            Cuando el stock llegue a este número o menos, el producto se marcará como stock bajo. Usa 0 para no tener alerta.
          </p>
        </div>

        <?php if (count($categoryOptions) > 1): ?>
          <div>
            <label for="product-category" class="mb-1.5 block text-sm font-semibold">Categoría</label>
            <select id="product-category" name="category_id" required class="<?= $input ?>">
              <?php foreach ($categoryOptions as $index => $option): ?>
                <option value="<?= (int) $option["id"] ?>" <?= $selectedCategory === (int) $option["id"] ? "selected" : "" ?>>
                  <?= html((string) $option["name"]) ?><?= $index === 0 ? " (toda la categoría)" : "" ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>
        <?php elseif ($categoryOptions): ?>
          <div>
            <p class="mb-1.5 text-sm font-semibold">Categoría</p>
            <p class="text-base"><?= html((string) $categoryOptions[0]["name"]) ?></p>
            <p class="mt-1 text-sm text-ink/80">Los productos de esta góndola usan su misma categoría.</p>
          </div>
        <?php endif; ?>

        <div class="flex flex-col-reverse gap-3 pt-2 sm:flex-row sm:justify-end">
          <button type="button" data-close class="<?= $btnSecondary ?>">Cancelar</button>
          <button type="submit" class="<?= $btnPrimary ?>">Guardar producto</button>
        </div>
      </form>
    </div>
  </dialog>

  <?php if ($products): ?>
    <script>
      // STOCK: los botones − y + solo cambian el número del campo; el cambio se aplica al pulsar "Guardar".
      // Sin JS siguen funcionando el campo y el botón Guardar (los − / + quedan ocultos).
      (function () {
        try {
          var MAX = 9999999;

          document.querySelectorAll('[data-stock-form]').forEach(function (form) {
            var input = form.querySelector('input[name="new_stock"]');
            if (!input) return;

            form.querySelectorAll('[data-stock-step]').forEach(function (btn) {
              btn.classList.remove('hidden');
              btn.classList.add('inline-flex');

              btn.addEventListener('click', function () {
                var step = parseInt(btn.getAttribute('data-stock-step'), 10);
                var current = parseInt(input.value, 10);
                if (isNaN(current)) current = 0;
                input.value = String(Math.min(MAX, Math.max(0, current + step)));
              });
            });
          });
        } catch (err) {
          console.error('Stock:', err);
        }
      })();
    </script>
  <?php endif; ?>

<?php endif; ?>
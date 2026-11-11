<?php
/**
 * src/views/layouts/app.php
 *
 * Layout intermedio: columna lateral + franja superior + área principal.
 * NO usar para la caja registradora (esa tiene su propia pantalla).
 *
 * Variables:
 *   $content       string  HTML de la vista (lo inyecta view()).
 *   $userName      string  Nombre del usuario logueado (por defecto "Usuario").
 *   $activeNav     string  "inicio" | "repositorios" | "caja" (ítem activo).
 *   $logoutMethod  string  "POST" (por defecto) o "GET".
 *   $logoutAction  string  URL del logout (por defecto "/logout").
 *   $csrf          array|null  ["name" => "...", "value" => "..."] si el proyecto usa CSRF.
 */
$userName     = trim((string) ($userName ?? ""));
$userName     = $userName !== "" ? $userName : "Usuario";
$activeNav    = (string) ($activeNav ?? "");
$logoutMethod = strtoupper((string) ($logoutMethod ?? "POST"));
$logoutAction = (string) ($logoutAction ?? "/logout");
$csrf         = $csrf ?? null;

$img = "/images-suppli/";

$navItem = "flex items-center justify-center gap-3 rounded-lg px-2 py-2.5 text-sm font-medium text-ink "
  . "transition-colors hover:bg-ink/10 focus-visible:outline focus-visible:outline-2 "
  . "focus-visible:outline-offset-2 focus-visible:outline-ink lg:justify-start lg:px-3";

$items = [
  ["key" => "inicio",       "href" => "/dashboard",    "icon" => "botonHome.png", "label" => "Inicio"],
  // TODO: crear la ruta /repositorios
  ["key" => "repositorios", "href" => "/repositorios", "icon" => "botonRepo.png",   "label" => "Repositorios"],
  // TODO: crear la ruta /caja (vista aparte, sin este layout)
  ["key" => "caja",         "href" => "/caja",         "icon" => "botonCaja.png",   "label" => "Caja registradora"],
];

$logoutClass = "flex w-full items-center justify-center gap-3 rounded-lg border border-danger px-2 py-2.5 "
  . "text-sm font-semibold text-danger transition-colors hover:bg-danger hover:text-white "
  . "focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 "
  . "focus-visible:outline-danger lg:justify-start lg:px-3";

$logoutIcon = '<svg class="h-6 w-6 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" '
  . 'stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">'
  . '<path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><path d="M16 17l5-5-5-5"/><path d="M21 12H9"/></svg>';
?>
<div class="fixed inset-0 grid grid-cols-[minmax(4rem,15%)_minmax(0,1fr)] grid-rows-[minmax(3.5rem,10%)_minmax(0,1fr)] overflow-hidden">

  <!-- COLUMNA IZQUIERDA -->
  <aside class="relative z-20 row-span-2 flex min-h-0 flex-col gap-3 border-r border-ink/20 bg-canvas p-2 lg:p-4">

    <div class="flex items-center justify-center gap-3 border-b border-ink/15 px-1 pb-3 pt-1 lg:justify-start lg:px-2"
         title="<?= html($userName) ?>">
      <img src="<?= $img ?>botonUser.png" alt="" width="28" height="28" class="icon-themed h-7 w-7 shrink-0">
      <span class="hidden min-w-0 flex-1 truncate text-sm font-semibold lg:block"><?= html($userName) ?></span>
      <span class="sr-only lg:hidden">Usuario: <?= html($userName) ?></span>
    </div>

    <nav aria-label="Principal">
      <ul class="space-y-1">
        <?php foreach ($items as $item): ?>
          <?php $isActive = $activeNav === $item["key"]; ?>
          <li>
            <a href="<?= html($item["href"]) ?>"
               <?= $isActive ? 'aria-current="page"' : "" ?>
               class="<?= $navItem ?><?= $isActive ? " bg-ink/10 font-semibold" : "" ?>">
              <img src="<?= $img . html($item["icon"]) ?>" alt="" width="24" height="24" class="icon-themed h-6 w-6 shrink-0">
              <span class="sr-only lg:not-sr-only"><?= html($item["label"]) ?></span>
            </a>
          </li>
        <?php endforeach; ?>

        <!-- Tema -->
        <li class="relative" id="theme-wrap">
          <button type="button" id="theme-btn" class="<?= $navItem ?> w-full"
                  aria-haspopup="menu" aria-expanded="false" aria-controls="theme-menu">
            <img src="<?= $img ?>botonTema.png" alt="" width="24" height="24" class="icon-themed h-6 w-6 shrink-0">
            <span class="sr-only lg:not-sr-only">Tema</span>
          </button>

          <div id="theme-menu" role="menu" aria-label="Tema" hidden
               class="absolute left-full top-0 z-30 ml-2 w-60 rounded-xl border border-ink/20 bg-card p-1.5 shadow-lg">
            <button type="button" role="menuitemradio" aria-checked="true" tabindex="-1" data-theme-option="suppli"
                    class="flex w-full items-center gap-3 rounded-lg px-3 py-2 text-left text-sm font-medium text-ink hover:bg-ink/10 focus-visible:outline focus-visible:outline-2 focus-visible:outline-ink">
              <span aria-hidden="true" class="h-5 w-5 shrink-0 rounded-full border border-black/20"
                    style="background:linear-gradient(135deg,#f5f0e8 50%,#294370 50%)"></span>
              <span class="flex-1">Suppli</span>
            </button>
            <button type="button" role="menuitemradio" aria-checked="false" tabindex="-1" data-theme-option="bn"
                    class="flex w-full items-center gap-3 rounded-lg px-3 py-2 text-left text-sm font-medium text-ink hover:bg-ink/10 focus-visible:outline focus-visible:outline-2 focus-visible:outline-ink">
              <span aria-hidden="true" class="h-5 w-5 shrink-0 rounded-full border border-black/20"
                    style="background:linear-gradient(135deg,#ffffff 50%,#000000 50%)"></span>
              <span class="flex-1">Blanco y negro</span>
            </button>
          </div>
        </li>
      </ul>
    </nav>

    <!-- Cerrar sesión (único elemento rojo) -->
    <div class="mt-auto">
      <?php if ($logoutMethod === "GET"): ?>
        <a href="<?= html($logoutAction) ?>" class="<?= $logoutClass ?>">
          <?= $logoutIcon ?>
          <span class="sr-only lg:not-sr-only">Cerrar sesión</span>
        </a>
      <?php else: ?>
        <form action="<?= html($logoutAction) ?>" method="POST">
          <?php if (is_array($csrf) && isset($csrf["name"], $csrf["value"])): ?>
            <input type="hidden" name="<?= html((string) $csrf["name"]) ?>" value="<?= html((string) $csrf["value"]) ?>">
          <?php endif; ?>
          <button type="submit" class="<?= $logoutClass ?>">
            <?= $logoutIcon ?>
            <span class="sr-only lg:not-sr-only">Cerrar sesión</span>
          </button>
        </form>
      <?php endif; ?>
    </div>
  </aside>

  <!-- FRANJA SUPERIOR -->
  <header class="flex items-center justify-between gap-3 bg-bar px-3 text-onbar lg:gap-6 lg:px-6">
    <!-- TODO: búsqueda real (por ahora solo visual) -->
    <form role="search" action="#" class="min-w-0 max-w-xs flex-1 sm:max-w-md lg:max-w-xl">
      <label for="buscador" class="sr-only">Buscar productos, repositorios o góndolas</label>
      <input type="search" id="buscador" name="q" autocomplete="off"
             placeholder="Buscar productos, repositorios, góndolas…"
             class="w-full rounded-lg border border-onbar/30 bg-field px-3.5 py-2 text-sm text-ink placeholder:text-ink/80 focus:outline-none focus:ring-2 focus:ring-onbar">
    </form>

    <a href="/dashboard" class="shrink-0 rounded focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-onbar">
      <img src="<?= $img ?>supplipngCrema.png" alt="Suppli, ir al inicio" width="120" height="36" class="h-9 w-auto">
    </a>
  </header>

   <!-- ÁREA PRINCIPAL -->
  <main id="contenido" class="min-h-0 overflow-y-auto bg-canvas text-ink">
    <?php $flash = pull_flash(); ?>
    <?php if ($flash !== null): ?>
      <div class="mx-auto w-full max-w-5xl px-6 pt-6">
        <p role="status" class="rounded-lg border border-ink/30 bg-card px-4 py-3 text-sm font-medium">
          <?= html($flash) ?>
        </p>
      </div>
    <?php endif; ?>
    <?= $content ?? "" ?>
  </main>
</div>

<script>
  // Viñetas (<dialog>) reutilizables.
  //   Botón que abre:   data-dialog-open="id-de-la-viñeta"
  //   La viñeta:        <dialog data-dialog data-autoopen="0|1"> ... </dialog>
  //   Cierre:           cualquier elemento con data-close
  //   Foco inicial:     elemento con data-autofocus (si no, el primer campo)
  (function () {
    if (typeof HTMLDialogElement === 'undefined') return;

    var scrollArea = document.getElementById('contenido');

    function lockScroll(lock) {
      document.documentElement.style.overflow = lock ? 'hidden' : '';
      if (scrollArea) scrollArea.style.overflow = lock ? 'hidden' : '';
    }

    function openDialog(dialog, opener) {
      if (dialog.open) return;
      dialog.__opener = opener || null;
      dialog.showModal();
      lockScroll(true);
      var first = dialog.querySelector('[data-autofocus]') ||
                  dialog.querySelector('input:not([type="hidden"]), select, textarea');
      if (first) first.focus();
    }

    document.querySelectorAll('[data-dialog-open]').forEach(function (btn) {
      btn.addEventListener('click', function () {
        var dialog = document.getElementById(btn.getAttribute('data-dialog-open'));
        if (dialog) openDialog(dialog, btn);
      });
    });

    document.querySelectorAll('dialog[data-dialog]').forEach(function (dialog) {
      var downOnBackdrop = false;

      dialog.querySelectorAll('[data-close]').forEach(function (el) {
        el.addEventListener('click', function () { dialog.close(); });
      });

      // Clic en el fondo oscuro (evita cerrar si se arrastró una selección desde dentro)
      dialog.addEventListener('mousedown', function (e) { downOnBackdrop = (e.target === dialog); });
      dialog.addEventListener('click', function (e) {
        if (e.target === dialog && downOnBackdrop) dialog.close();
        downOnBackdrop = false;
      });

      // Se dispara con ×, Cancelar, Esc y clic en el fondo
      dialog.addEventListener('close', function () {
        lockScroll(false);
        if (dialog.__opener) dialog.__opener.focus();
      });

      // Si el servidor devolvió errores, abrir al cargar
      if (dialog.getAttribute('data-autoopen') === '1') {
        openDialog(dialog, document.querySelector('[data-dialog-open="' + dialog.id + '"]'));
      }
    });
  })();
</script>
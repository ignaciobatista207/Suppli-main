<?php
/**
 * src/views/layouts/caja.php
 *
 * Layout de la caja registradora: encabezado propio y SIN columna lateral.
 * Se usa con view($renderer, $response, "caja/xxx.php", $data, "layouts/caja.php"),
 * que lo envuelve en layouts/base.php (hereda tema, fuentes y tokens de color).
 *
 * Variables:
 *   $content     string      HTML de la vista (lo inyecta view()).
 *   $repository  array       id, name, role.
 *   $userName    string      Operador (usuario con sesión iniciada).
 *   $session     array|null  Turno abierto (opened_by_name, opened_at) o null.
 *   $canClose    bool        Si el usuario puede cerrar el turno (quien lo abrió o un admin).
 *   $cajaNav     string      "venta" | "historial" | "cierre" (enlace activo).
 */
use App\Models\Dates;

$repository = is_array($repository ?? null) ? $repository : [];
$session    = is_array($session ?? null) ? $session : null;
$userName   = trim((string) ($userName ?? ""));
$userName   = $userName !== "" ? $userName : "Usuario";
$cajaNav    = (string) ($cajaNav ?? "");
$canClose   = (bool) ($canClose ?? false);
$repoId     = (int) ($repository["id"] ?? 0);
$img        = "/images-suppli/";
$flash      = pull_flash();

$ring = "focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-onbar";
$link = "inline-flex min-h-[44px] items-center rounded-lg border border-onbar/40 px-4 text-sm font-semibold text-onbar "
  . "transition-colors hover:bg-onbar/15 " . $ring;

$links = [
  ["key" => "venta",     "href" => "/repositorios/" . $repoId . "/caja",        "label" => "Caja"],
  ["key" => "historial", "href" => "/repositorios/" . $repoId . "/caja/ventas", "label" => "Historial"],
];

if ($canClose) {
  $links[] = ["key" => "cierre", "href" => "/repositorios/" . $repoId . "/caja/cierre", "label" => "Cerrar caja"];
}
?>
<div class="flex min-h-screen flex-col">

  <header class="bg-bar px-4 py-3 text-onbar">
    <div class="mx-auto flex w-full max-w-7xl flex-wrap items-center justify-between gap-x-6 gap-y-3">

      <div class="flex min-w-0 items-center gap-4">
        <a href="/repositorios/<?= $repoId ?>/caja" class="shrink-0 rounded <?= $ring ?>">
          <img src="<?= $img ?>supplipngCrema.png" alt="Suppli, ir a la caja" width="120" height="36" class="h-9 w-auto">
        </a>
        <div class="min-w-0 text-sm">
          <p class="truncate text-base font-bold" title="<?= html((string) ($repository["name"] ?? "")) ?>">
            <?= html((string) ($repository["name"] ?? "")) ?>
          </p>
          <p class="truncate">Operador: <?= html($userName) ?></p>
          <?php if ($session !== null): ?>
            <p class="truncate">
              Turno abierto por <?= html((string) $session["opened_by_name"]) ?>
              desde <?= html(Dates::format((string) $session["opened_at"], "d/m/Y H:i")) ?>
            </p>
          <?php endif; ?>
        </div>
      </div>

      <nav aria-label="Caja" class="flex flex-wrap items-center gap-2">
        <?php foreach ($links as $item): ?>
          <?php $isActive = $cajaNav === $item["key"]; ?>
          <a href="<?= html($item["href"]) ?>"
             <?= $isActive ? 'aria-current="page"' : "" ?>
             class="<?= $link ?><?= $isActive ? " bg-onbar/20" : "" ?>">
            <?= html($item["label"]) ?>
          </a>
        <?php endforeach; ?>
        <a href="/dashboard" class="<?= $link ?>">Salir</a>

        <!-- Tema (mismos ids, misma clave de localStorage y mismo comportamiento que en la columna lateral) -->
        <div class="relative" id="theme-wrap">
          <button type="button" id="theme-btn" class="<?= $link ?> gap-2"
                  aria-haspopup="menu" aria-expanded="false" aria-controls="theme-menu">
            <img src="<?= $img ?>botonTema.png" alt="" width="24" height="24" class="icon-on-accent h-6 w-6 shrink-0">
            <span>Tema</span>
          </button>

          <div id="theme-menu" role="menu" aria-label="Tema" hidden
               class="absolute right-0 top-full z-40 mt-2 w-60 rounded-xl border border-ink/20 bg-card p-1.5 text-ink shadow-lg">
            <button type="button" role="menuitemradio" aria-checked="true" tabindex="-1" data-theme-option="suppli"
                    class="flex w-full items-center gap-3 rounded-lg px-3 py-2 text-left text-sm font-medium text-ink hover:bg-ink/10 focus-visible:outline focus-visible:outline-2 focus-visible:outline-ink">
              <span aria-hidden="true" class="h-5 w-5 shrink-0 rounded-full border border-black/20"
                    style="background:linear-gradient(135deg,#f5f0e8 50%,#294370 50%)"></span>
              <span class="flex-1">Original (Suppli)</span>
            </button>
            <button type="button" role="menuitemradio" aria-checked="false" tabindex="-1" data-theme-option="bn"
                    class="flex w-full items-center gap-3 rounded-lg px-3 py-2 text-left text-sm font-medium text-ink hover:bg-ink/10 focus-visible:outline focus-visible:outline-2 focus-visible:outline-ink">
              <span aria-hidden="true" class="h-5 w-5 shrink-0 rounded-full border border-black/20"
                    style="background:linear-gradient(135deg,#ffffff 50%,#000000 50%)"></span>
              <span class="flex-1">Blanco y negro</span>
            </button>
          </div>
        </div>
      </nav>
    </div>
  </header>

  <?php if ($flash !== null): ?>
    <div class="mx-auto w-full max-w-7xl px-4 pt-4">
      <p role="status" class="rounded-lg border border-ink/30 bg-card px-4 py-3 text-sm font-medium">
        <?= html($flash) ?>
      </p>
    </div>
  <?php endif; ?>

  <main id="contenido" class="flex-1 text-ink">
    <?= $content ?? "" ?>
  </main>
</div>

<script>
  // TEMA (misma lógica que layouts/app.php; el script anti-parpadeo está en layouts/base.php)
  (function () {
    try {
      var root = document.documentElement;
      var wrap = document.getElementById('theme-wrap');
      var btn  = document.getElementById('theme-btn');
      var menu = document.getElementById('theme-menu');
      if (!wrap || !btn || !menu) return;

      var items = Array.prototype.slice.call(menu.querySelectorAll('[role="menuitemradio"]'));

      function current() { return root.getAttribute('data-theme') === 'bn' ? 'bn' : 'suppli'; }

      function sync() {
        items.forEach(function (item) {
          item.setAttribute('aria-checked', String(item.getAttribute('data-theme-option') === current()));
        });
      }

      function isOpen() { return !menu.hidden; }

      function openMenu() {
        menu.hidden = false;
        btn.setAttribute('aria-expanded', 'true');
        sync();
        var selected = items.filter(function (i) { return i.getAttribute('aria-checked') === 'true'; })[0];
        (selected || items[0]).focus();
      }

      function closeMenu(returnFocus) {
        menu.hidden = true;
        btn.setAttribute('aria-expanded', 'false');
        if (returnFocus) btn.focus();
      }

      function applyTheme(theme) {
        root.setAttribute('data-theme', theme);
        try { localStorage.setItem('suppli-theme', theme); } catch (err) {}
        sync();
      }

      btn.addEventListener('click', function () { if (isOpen()) closeMenu(false); else openMenu(); });
      btn.addEventListener('keydown', function (e) {
        if (e.key === 'ArrowDown' || e.key === 'ArrowUp') { e.preventDefault(); if (!isOpen()) openMenu(); }
      });

      items.forEach(function (item) {
        item.addEventListener('click', function () {
          applyTheme(item.getAttribute('data-theme-option'));
          closeMenu(true);
        });
      });

      menu.addEventListener('keydown', function (e) {
        var i = items.indexOf(document.activeElement);
        if (e.key === 'ArrowDown')    { e.preventDefault(); items[(i + 1) % items.length].focus(); }
        else if (e.key === 'ArrowUp') { e.preventDefault(); items[(i - 1 + items.length) % items.length].focus(); }
        else if (e.key === 'Home')    { e.preventDefault(); items[0].focus(); }
        else if (e.key === 'End')     { e.preventDefault(); items[items.length - 1].focus(); }
        else if (e.key === 'Tab')     { e.preventDefault(); closeMenu(true); }
      });

      document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape' && isOpen()) { e.preventDefault(); closeMenu(true); }
      });

      document.addEventListener('click', function (e) {
        if (isOpen() && !wrap.contains(e.target)) closeMenu(false);
      });

      sync();
    } catch (err) {
      console.error('Tema:', err);
    }
  })();
</script>
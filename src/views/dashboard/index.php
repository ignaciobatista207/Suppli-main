<?php
/**
 * src/views/dashboard.php
 *
 * Solo el contenido del área principal. Se renderiza dentro de layouts/app.php.
 *
 * Variables opcionales:
 *   $errors  string[]    Errores de validación (abren la viñeta automáticamente).
 *   $old     array       ['name' => '...'] para repoblar el nombre (nunca la contraseña).
 *   $csrf    array|null  ["name" => "...", "value" => "..."] si el proyecto usa CSRF.
 */
$errors = is_array($errors ?? null) ? $errors : [];
$old    = is_array($old ?? null) ? $old : [];
$csrf   = $csrf ?? null;
$img    = "/images-suppli/";

$focusRing = "focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-ink";
?>
<div class="m-auto p-6">
  <button type="button" id="open-repo" aria-haspopup="dialog"
          class="inline-flex items-center gap-3 rounded-xl bg-accent px-8 py-5 text-lg font-semibold text-onaccent shadow-sm transition-colors hover:bg-accenth <?= $focusRing ?>">
    <img src="<?= $img ?>botonMas.png" alt="" width="28" height="28" class="icon-on-accent h-7 w-7 shrink-0">
    <span>Crear repositorio</span>
  </button>
</div>

<dialog id="repo-dialog" aria-labelledby="repo-dialog-title"
        data-autoopen="<?= $errors ? "1" : "0" ?>"
        class="m-auto w-[calc(100%-2rem)] max-w-md rounded-2xl bg-card p-0 text-ink shadow-2xl backdrop:bg-black/60">
  <div class="p-6">
    <div class="flex items-start justify-between gap-4">
      <h2 id="repo-dialog-title" class="font-display text-2xl font-extrabold tracking-tight">Crear repositorio</h2>
      <button type="button" data-close aria-label="Cerrar"
              class="-mr-2 -mt-2 rounded-lg px-3 py-1 text-2xl leading-none text-ink hover:bg-ink/10 <?= $focusRing ?>">
        <span aria-hidden="true">&times;</span>
      </button>
    </div>

    <?php if ($errors): ?>
      <div role="alert" class="mt-4 rounded-lg border border-danger/40 bg-danger/10 px-4 py-3 text-sm text-danger">
        <p class="font-semibold">Revisa estos datos:</p>
        <ul class="mt-1 list-disc space-y-0.5 pl-5">
          <?php foreach ($errors as $msg): ?>
            <li><?= html((string) $msg) ?></li>
          <?php endforeach; ?>
        </ul>
      </div>
    <?php endif; ?>

    <!--
      TODO: implementar POST /repositorios (ruta, controlador y tabla).
      La contraseña del repositorio debe guardarse con password_hash(), nunca en texto plano.
    -->
    <form action="/repositorios" method="POST" class="mt-5 space-y-4">
      <?php if (is_array($csrf) && isset($csrf["name"], $csrf["value"])): ?>
        <input type="hidden" name="<?= html((string) $csrf["name"]) ?>" value="<?= html((string) $csrf["value"]) ?>">
      <?php endif; ?>

      <div>
        <label for="repo-name" class="mb-1.5 block text-sm font-semibold">Nombre del repositorio</label>
        <input type="text" id="repo-name" name="name" required maxlength="100" autocomplete="off"
               value="<?= html((string) ($old["name"] ?? "")) ?>"
               class="w-full rounded-lg border border-ink/30 bg-card px-3.5 py-2.5 text-base text-ink focus:border-ink focus:outline-none focus:ring-2 focus:ring-ink/30">
      </div>

      <div>
        <label for="repo-password" class="mb-1.5 block text-sm font-semibold">Contraseña del repositorio</label>
        <input type="password" id="repo-password" name="password" required autocomplete="new-password"
               aria-describedby="repo-password-hint"
               class="w-full rounded-lg border border-ink/30 bg-card px-3.5 py-2.5 text-base text-ink focus:border-ink focus:outline-none focus:ring-2 focus:ring-ink/30">
        <p id="repo-password-hint" class="mt-1.5 text-sm text-ink/80">Con esta contraseña otros usuarios podrán unirse al repositorio.</p>
      </div>

      <div class="flex flex-col-reverse gap-3 pt-2 sm:flex-row sm:justify-end">
        <button type="button" data-close
                class="rounded-lg border border-ink/30 px-5 py-2.5 text-sm font-semibold text-ink transition-colors hover:bg-ink/10 <?= $focusRing ?>">
          Cancelar
        </button>
        <button type="submit"
                class="inline-flex items-center justify-center gap-2 rounded-lg bg-accent px-5 py-2.5 text-sm font-semibold text-onaccent transition-colors hover:bg-accenth <?= $focusRing ?>">
          <img src="<?= $img ?>botonMas.png" alt="" width="20" height="20" class="icon-on-accent h-5 w-5 shrink-0">
          <span>Crear repositorio</span>
        </button>
      </div>
    </form>
  </div>
</dialog>

<script>
  (function () {
    var dialog     = document.getElementById('repo-dialog');
    var opener     = document.getElementById('open-repo');
    var nameInput  = document.getElementById('repo-name');
    var scrollArea = document.getElementById('contenido');
    if (!dialog || !opener || typeof dialog.showModal !== 'function') return;

    var downOnBackdrop = false;

    function lockScroll(lock) {
      document.documentElement.style.overflow = lock ? 'hidden' : '';
      if (scrollArea) scrollArea.style.overflow = lock ? 'hidden' : '';
    }

    function openDialog() {
      dialog.showModal();
      lockScroll(true);
      nameInput.focus();
    }

    opener.addEventListener('click', openDialog);

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
      opener.focus();
    });

    // Si el servidor devolvió errores, abrir al cargar
    if (dialog.dataset.autoopen === '1') openDialog();
  })();
</script>
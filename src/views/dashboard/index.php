<?php
/**
 * src/views/dashboard/index.php
 *
 * Contenido del área principal. Se renderiza dentro de layouts/app.php.
 *
 * Variables:
 *   $repositories  array       Repositorios del usuario: id, name, color, role.
 *   $errors        string[]    Errores de validación (abren la viñeta automáticamente).
 *   $old           array       ['name' => '...'] para repoblar el nombre (nunca la contraseña).
 *   $csrf          array|null  ["name" => "...", "value" => "..."] si el proyecto usa CSRF.
 */
$repositories = is_array($repositories ?? null) ? $repositories : [];
$errors       = is_array($errors ?? null) ? $errors : [];
$old          = is_array($old ?? null) ? $old : [];
$csrf         = $csrf ?? null;
$img          = "/images-suppli/";

$focusRing   = "focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-ink";
$btnPrimary  = "inline-flex items-center justify-center gap-3 rounded-xl bg-accent px-6 py-3 text-base font-semibold text-onaccent shadow-sm transition-colors hover:bg-accenth " . $focusRing;
$btnSecondary = "rounded-lg border border-ink/30 px-5 py-2.5 text-sm font-semibold text-ink transition-colors hover:bg-ink/10 " . $focusRing;
$input       = "w-full rounded-lg border border-ink/30 bg-card px-3.5 py-2.5 text-base text-ink focus:border-ink focus:outline-none focus:ring-2 focus:ring-ink/30";
?>
<div class="mx-auto w-full max-w-5xl p-6">

  <div class="flex flex-wrap items-center justify-between gap-4">
    <h1 class="font-display text-2xl font-extrabold tracking-tight">Tus repositorios</h1>

    <?php if ($repositories): ?>
      <button type="button" data-dialog-open="repo-dialog" aria-haspopup="dialog" class="<?= $btnPrimary ?>">
        <img src="<?= $img ?>botonMas.png" alt="" width="24" height="24" class="icon-on-accent h-6 w-6 shrink-0">
        <span>Crear repositorio</span>
      </button>
    <?php endif; ?>
  </div>

  <?php if (!$repositories): ?>
    <div class="mt-6 flex flex-col items-center gap-5 rounded-2xl border border-dashed border-ink/30 px-6 py-14 text-center">
      <p class="text-lg font-medium">Todavía no tienes repositorios. Crea el primero.</p>
      <button type="button" data-dialog-open="repo-dialog" aria-haspopup="dialog" class="<?= $btnPrimary ?>">
        <img src="<?= $img ?>botonMas.png" alt="" width="24" height="24" class="icon-on-accent h-6 w-6 shrink-0">
        <span>Crear repositorio</span>
      </button>
    </div>
  <?php else: ?>
    <ul class="mt-6 grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
      <?php foreach ($repositories as $repo): ?>
        <li>
          <a href="/repositorios/<?= (int) $repo["id"] ?>"
             class="block overflow-hidden rounded-xl border border-ink/20 bg-card shadow-sm transition-shadow hover:shadow-md <?= $focusRing ?>">
            <div class="h-2.5" style="background-color: <?= html(\App\Models\Palette::safe((string) $repo["color"])) ?>" aria-hidden="true"></div>
            <div class="p-4">
              <h2 class="truncate font-display text-lg font-extrabold" title="<?= html((string) $repo["name"]) ?>">
                <?= html((string) $repo["name"]) ?>
              </h2>
              <p class="mt-2">
                <span class="inline-flex rounded-full border border-ink/30 px-2.5 py-0.5 text-xs font-semibold">
                  <?= $repo["role"] === "admin" ? "Administrador" : "Miembro" ?>
                </span>
              </p>
            </div>
          </a>
        </li>
      <?php endforeach; ?>
    </ul>
  <?php endif; ?>
</div>

<dialog id="repo-dialog" data-dialog aria-labelledby="repo-dialog-title"
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

    <!-- TODO (pendiente): token CSRF en este formulario -->
    <form action="/repositorios" method="POST" class="mt-5 space-y-4">
      <?php if (is_array($csrf) && isset($csrf["name"], $csrf["value"])): ?>
        <input type="hidden" name="<?= html((string) $csrf["name"]) ?>" value="<?= html((string) $csrf["value"]) ?>">
      <?php endif; ?>

      <div>
        <label for="repo-name" class="mb-1.5 block text-sm font-semibold">Nombre del repositorio</label>
        <input type="text" id="repo-name" name="name" required maxlength="100" autocomplete="off" data-autofocus
               value="<?= html((string) ($old["name"] ?? "")) ?>"
               class="<?= $input ?>">
      </div>

      <div>
        <label for="repo-password" class="mb-1.5 block text-sm font-semibold">Contraseña del repositorio</label>
        <input type="password" id="repo-password" name="password" required minlength="8" autocomplete="new-password"
               aria-describedby="repo-password-hint"
               class="<?= $input ?>">
        <p id="repo-password-hint" class="mt-1.5 text-sm text-ink/80">Mínimo 8 caracteres. Con esta contraseña otros usuarios podrán unirse al repositorio.</p>
      </div>

      <div class="flex flex-col-reverse gap-3 pt-2 sm:flex-row sm:justify-end">
        <button type="button" data-close class="<?= $btnSecondary ?>">Cancelar</button>
        <button type="submit"
                class="inline-flex items-center justify-center gap-2 rounded-lg bg-accent px-5 py-2.5 text-sm font-semibold text-onaccent transition-colors hover:bg-accenth <?= $focusRing ?>">
          <img src="<?= $img ?>botonMas.png" alt="" width="20" height="20" class="icon-on-accent h-5 w-5 shrink-0">
          <span>Crear repositorio</span>
        </button>
      </div>
    </form>
  </div>
</dialog>
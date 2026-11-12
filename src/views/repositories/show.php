<?php
/**
 * src/views/repositories/show.php
 *
 * Página interna de un repositorio. Se renderiza dentro de layouts/app.php.
 *
 * Variables:
 *   $repository    array   id, name, color, role ("admin" | "member").
 *   $isAdmin       bool
 *   $gondolas      array   id, name, color, category_name, parent_category_name.
 *   $categoryTree  array   Category::tree() (solo se carga para el admin).
 *   $errors        string[]  Errores de la viñeta que se abre sola.
 *   $old           array   Valores previos del formulario (nunca contraseñas).
 *   $openDialog    string|null  "edit" | "gondola".
 *   $csrf          array|null  ["name" => "...", "value" => "..."] si el proyecto usa CSRF.
 */
use App\Models\Palette;

$repository   = is_array($repository ?? null) ? $repository : [];
$isAdmin      = (bool) ($isAdmin ?? false);
$gondolas     = is_array($gondolas ?? null) ? $gondolas : [];
$categoryTree = is_array($categoryTree ?? null) ? $categoryTree : [];
$errors       = is_array($errors ?? null) ? $errors : [];
$old          = is_array($old ?? null) ? $old : [];
$openDialog   = $openDialog ?? null;
$csrf         = $csrf ?? null;
$img          = "/images-suppli/";
$repoId       = (int) $repository["id"];

$focusRing    = "focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-ink";
$btnPrimary   = "inline-flex items-center justify-center gap-2 rounded-lg bg-accent px-5 py-2.5 text-sm font-semibold text-onaccent transition-colors hover:bg-accenth " . $focusRing;
$btnSecondary = "rounded-lg border border-ink/30 px-5 py-2.5 text-sm font-semibold text-ink transition-colors hover:bg-ink/10 " . $focusRing;
$input        = "w-full rounded-lg border border-ink/30 bg-card px-3.5 py-2.5 text-base text-ink focus:border-ink focus:outline-none focus:ring-2 focus:ring-ink/30";
$dialogClass  = "m-auto w-[calc(100%-2rem)] max-w-md rounded-2xl bg-card p-0 text-ink shadow-2xl backdrop:bg-black/60";
$closeClass   = "-mr-2 -mt-2 rounded-lg px-3 py-1 text-2xl leading-none text-ink hover:bg-ink/10 " . $focusRing;

$editOld    = $openDialog === "edit" ? $old : [];
$gondolaOld = $openDialog === "gondola" ? $old : [];
$editColor  = Palette::normalize((string) ($editOld["color"] ?? "")) ?? (string) $repository["color"];
$gondolaCategory = (int) ($gondolaOld["category_id"] ?? 0);
?>
<div class="mx-auto w-full max-w-5xl p-6">

  <a href="/dashboard"
     class="inline-block rounded text-sm font-semibold underline-offset-2 hover:underline <?= $focusRing ?>">
    <span aria-hidden="true">←</span> Volver
  </a>

  <div class="mt-3 flex flex-wrap items-center justify-between gap-4">
    <h1 class="min-w-0 break-words font-display text-2xl font-extrabold tracking-tight">
      <?= html((string) $repository["name"]) ?>
    </h1>

    <?php if ($isAdmin): ?>
      <div class="flex flex-wrap gap-3">
        <button type="button" data-dialog-open="repo-edit-dialog" aria-haspopup="dialog" class="<?= $btnSecondary ?>">
          Editar repositorio
        </button>
        <button type="button" data-dialog-open="gondola-dialog" aria-haspopup="dialog" class="<?= $btnPrimary ?>">
          <img src="<?= $img ?>botonMas.png" alt="" width="20" height="20" class="icon-on-accent h-5 w-5 shrink-0">
          <span>Crear góndola</span>
        </button>
      </div>
    <?php endif; ?>
  </div>

  <?php if (!$gondolas): ?>
    <p class="mt-8 rounded-2xl border border-dashed border-ink/30 px-6 py-12 text-center text-lg font-medium">
      Este repositorio todavía no tiene góndolas.
    </p>
  <?php else: ?>
    <ul class="mt-6 grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
      <?php foreach ($gondolas as $gondola): ?>
        <li>
          <a href="/repositorios/<?= $repoId ?>/gondolas/<?= (int) $gondola["id"] ?>"
             class="block overflow-hidden rounded-xl border border-ink/20 bg-card shadow-sm transition-shadow hover:shadow-md <?= $focusRing ?>">
            <div class="h-2.5" style="background-color: <?= html(Palette::safe((string) $gondola["color"])) ?>" aria-hidden="true"></div>
            <div class="p-4">
              <h2 class="truncate font-display text-lg font-extrabold" title="<?= html((string) $gondola["name"]) ?>">
                <?= html((string) $gondola["name"]) ?>
              </h2>
              <p class="mt-1 text-sm text-ink/80">
                <?= $gondola["parent_category_name"] !== null
                  ? html((string) $gondola["parent_category_name"]) . " › " . html((string) $gondola["category_name"])
                  : html((string) $gondola["category_name"]) ?>
              </p>
            </div>
          </a>
        </li>
      <?php endforeach; ?>
    </ul>
  <?php endif; ?>
</div>

<?php if ($isAdmin): ?>

  <!-- VIÑETA: EDITAR REPOSITORIO -->
  <dialog id="repo-edit-dialog" data-dialog aria-labelledby="repo-edit-title"
          data-autoopen="<?= ($errors && $openDialog === "edit") ? "1" : "0" ?>"
          class="<?= $dialogClass ?>">
    <div class="p-6">
      <div class="flex items-start justify-between gap-4">
        <h2 id="repo-edit-title" class="font-display text-2xl font-extrabold tracking-tight">Editar repositorio</h2>
        <button type="button" data-close aria-label="Cerrar" class="<?= $closeClass ?>">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>

      <?php if ($errors && $openDialog === "edit"): ?>
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
      <form action="/repositorios/<?= $repoId ?>/editar" method="POST" class="mt-5 space-y-4">
        <?php if (is_array($csrf) && isset($csrf["name"], $csrf["value"])): ?>
          <input type="hidden" name="<?= html((string) $csrf["name"]) ?>" value="<?= html((string) $csrf["value"]) ?>">
        <?php endif; ?>

        <div>
          <label for="edit-repo-name" class="mb-1.5 block text-sm font-semibold">Nombre del repositorio</label>
          <input type="text" id="edit-repo-name" name="name" required maxlength="100" autocomplete="off" data-autofocus
                 value="<?= html((string) ($editOld["name"] ?? $repository["name"])) ?>"
                 class="<?= $input ?>">
        </div>

        <div>
          <label for="edit-repo-password" class="mb-1.5 block text-sm font-semibold">Contraseña nueva (opcional)</label>
          <input type="password" id="edit-repo-password" name="password" minlength="8" autocomplete="new-password"
                 aria-describedby="edit-repo-password-hint"
                 class="<?= $input ?>">
          <p id="edit-repo-password-hint" class="mt-1.5 text-sm text-ink/80">Déjala vacía para conservar la actual.</p>
        </div>

        <?= $this->fetch("partials/color-picker.php", ["idPrefix" => "edit", "selected" => $editColor]) ?>

        <div class="flex flex-col-reverse gap-3 pt-2 sm:flex-row sm:justify-end">
          <button type="button" data-close class="<?= $btnSecondary ?>">Cancelar</button>
          <button type="submit" class="<?= $btnPrimary ?>">Guardar cambios</button>
        </div>
      </form>
    </div>
  </dialog>

  <!-- VIÑETA: CREAR GÓNDOLA -->
  <dialog id="gondola-dialog" data-dialog aria-labelledby="gondola-dialog-title"
          data-autoopen="<?= ($errors && $openDialog === "gondola") ? "1" : "0" ?>"
          class="<?= $dialogClass ?>">
    <div class="p-6">
      <div class="flex items-start justify-between gap-4">
        <h2 id="gondola-dialog-title" class="font-display text-2xl font-extrabold tracking-tight">Crear góndola</h2>
        <button type="button" data-close aria-label="Cerrar" class="<?= $closeClass ?>">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>

      <?php if ($errors && $openDialog === "gondola"): ?>
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
      <form action="/repositorios/<?= $repoId ?>/gondolas" method="POST" class="mt-5 space-y-4">
        <?php if (is_array($csrf) && isset($csrf["name"], $csrf["value"])): ?>
          <input type="hidden" name="<?= html((string) $csrf["name"]) ?>" value="<?= html((string) $csrf["value"]) ?>">
        <?php endif; ?>

        <div>
          <label for="gondola-name" class="mb-1.5 block text-sm font-semibold">Nombre de la góndola</label>
          <input type="text" id="gondola-name" name="name" required maxlength="100" autocomplete="off" data-autofocus
                 value="<?= html((string) ($gondolaOld["name"] ?? "")) ?>"
                 class="<?= $input ?>">
        </div>

        <?= $this->fetch("partials/color-picker.php", [
          "idPrefix" => "gondola",
          "selected" => (string) ($gondolaOld["color"] ?? Palette::DEFAULT),
        ]) ?>

        <div>
          <label for="gondola-category" class="mb-1.5 block text-sm font-semibold">Categoría</label>
          <select id="gondola-category" name="category_id" required class="<?= $input ?>">
            <option value="">Elige una categoría</option>
            <?php foreach ($categoryTree as $parent): ?>
              <?php if ($parent["children"] === []): ?>
                <option value="<?= (int) $parent["id"] ?>" <?= $gondolaCategory === (int) $parent["id"] ? "selected" : "" ?>>
                  <?= html((string) $parent["name"]) ?>
                </option>
              <?php else: ?>
                <optgroup label="<?= html((string) $parent["name"]) ?>">
                  <option value="<?= (int) $parent["id"] ?>" <?= $gondolaCategory === (int) $parent["id"] ? "selected" : "" ?>>
                    <?= html((string) $parent["name"]) ?> (toda la categoría)
                  </option>
                  <?php foreach ($parent["children"] as $child): ?>
                    <option value="<?= (int) $child["id"] ?>" <?= $gondolaCategory === (int) $child["id"] ? "selected" : "" ?>>
                      <?= html((string) $child["name"]) ?>
                    </option>
                  <?php endforeach; ?>
                </optgroup>
              <?php endif; ?>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="flex flex-col-reverse gap-3 pt-2 sm:flex-row sm:justify-end">
          <button type="button" data-close class="<?= $btnSecondary ?>">Cancelar</button>
          <button type="submit" class="<?= $btnPrimary ?>">
            <img src="<?= $img ?>botonMas.png" alt="" width="20" height="20" class="icon-on-accent h-5 w-5 shrink-0">
            <span>Crear góndola</span>
          </button>
        </div>
      </form>
    </div>
  </dialog>

<?php endif; ?>
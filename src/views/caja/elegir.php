<?php
/**
 * src/views/caja/elegir.php
 *
 * Elegir el repositorio de la caja cuando el usuario tiene más de uno.
 * Se renderiza dentro de layouts/app.php (activeNav "caja").
 *
 * Variables:
 *   $repositories  array  id, name, color, role.
 */
use App\Models\Palette;

$repositories = is_array($repositories ?? null) ? $repositories : [];
$focusRing    = "focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-ink";
?>
<div class="mx-auto w-full max-w-5xl p-6">
  <h1 class="font-display text-2xl font-extrabold tracking-tight">Caja registradora</h1>
  <p class="mt-2 text-ink/80">Elige el repositorio en el que vas a cobrar.</p>

  <ul class="mt-6 grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
    <?php foreach ($repositories as $repo): ?>
      <li>
        <a href="/repositorios/<?= (int) $repo["id"] ?>/caja"
           class="block overflow-hidden rounded-xl border border-ink/20 bg-card shadow-sm transition-shadow hover:shadow-md <?= $focusRing ?>">
          <div class="h-2.5" style="background-color: <?= html(Palette::safe((string) $repo["color"])) ?>" aria-hidden="true"></div>
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
</div>
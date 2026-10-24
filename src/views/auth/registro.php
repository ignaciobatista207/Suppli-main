<?php
/**
 * src/views/auth/registro.php
 *
 * Variables opcionales que puede recibir desde el controlador:
 *   $errors  string[]  Lista de errores de validación (ej. ["Ese correo ya está registrado."])
 *   $old     array     Valores previos del formulario (ej. ['name' => 'Ana', 'email' => 'ana@mail.com'])
 */
$errors = $errors ?? [];
$old = $old ?? [];
?>
<!DOCTYPE html>
<html lang="es">
  <head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Crear cuenta | Suppli</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Bricolage+Grotesque:opsz,wght@12..96,600;12..96,800&family=DM+Sans:wght@400;500;600&display=swap" rel="stylesheet">

    <!-- Tailwind por CDN para desarrollar. Cuando compiles Tailwind, reemplaza este bloque por tu CSS. -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
      tailwind.config = {
        theme: {
          extend: {
            colors: {
              suppli: {
                DEFAULT: '#294370',
                dark: '#020202',
                cream: '#f5f0e8'
              }
            },
            fontFamily: {
              display: ['"Bricolage Grotesque"', 'system-ui', 'sans-serif'],
              sans: ['"DM Sans"', 'system-ui', 'sans-serif']
            }
          }
        }
      };
    </script>
  </head>
  <body class="bg-suppli-cream font-sans text-suppli-dark antialiased">

    <div class="relative grid min-h-screen grid-rows-[auto_1fr_auto] lg:grid-cols-2 lg:grid-rows-1">

      <!-- MITAD IZQUIERDA: marca (idéntica en las 3 pantallas) -->
      <header class="relative isolate overflow-hidden bg-suppli-dark px-6 py-8 text-center lg:flex lg:items-center lg:justify-center lg:px-16 lg:py-12">
        <img src="/images-suppli/editSuppli1.png" alt=""
             class="absolute inset-0 -z-20 h-full w-full object-cover">
        <div class="absolute inset-0 -z-10 bg-suppli/80" aria-hidden="true"></div>

        <a href="/"
           class="inline-block rounded focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-suppli-cream lg:absolute lg:left-12 lg:top-12">
          <img src="/images-suppli/pngsuppliCrema.png" alt="Suppli" class="h-auto w-32 lg:w-44">
        </a>

        <div class="mt-6 max-w-xl lg:mt-0">
          <p class="font-display text-3xl font-extrabold leading-tight tracking-tight text-suppli-cream sm:text-4xl lg:text-5xl">
            Tu stock y tu caja, en el mismo lugar
          </p>
          <p class="mt-3 text-base text-suppli-cream/95 lg:mt-5 lg:text-xl">
            Con Suppli, siempre sabes qué tienes a mano
          </p>
        </div>
      </header>

      <!-- MITAD DERECHA: formulario -->
      <main class="flex items-center justify-center px-6 py-12 lg:pb-24">
        <div class="w-full max-w-sm">

          <h1 class="font-display text-3xl font-extrabold tracking-tight">Crear cuenta</h1>
          <p class="mt-2 text-suppli-dark/80">Solo necesitas un correo y una contraseña.</p>

          <?php if (!empty($errors)): ?>
            <div role="alert" class="mt-6 rounded-md border border-red-300 bg-red-50 px-4 py-3 text-sm text-red-800">
              <p class="font-semibold">Revisa estos datos:</p>
              <ul class="mt-1 list-disc space-y-0.5 pl-5">
                <?php foreach ($errors as $msg): ?>
                  <li><?= html((string) $msg) ?></li>
                <?php endforeach; ?>
              </ul>
            </div>
          <?php endif; ?>

          <form action="/register" method="POST" class="mt-8 space-y-5">

            <div>
              <label for="name" class="mb-1.5 block text-sm font-semibold">Nombre</label>
              <input type="text" id="name" name="name" required autofocus
                     autocomplete="name" maxlength="100"
                     value="<?= html($old['name'] ?? '') ?>"
                     class="w-full rounded-md border border-suppli/60 bg-white px-3.5 py-2.5 text-base text-black focus:border-suppli focus:outline-none focus:ring-2 focus:ring-suppli">
            </div>

            <div>
              <label for="email" class="mb-1.5 block text-sm font-semibold">Correo electrónico</label>
              <input type="email" id="email" name="email" required
                     autocomplete="email" maxlength="150"
                     value="<?= html($old['email'] ?? '') ?>"
                     class="w-full rounded-md border border-suppli/60 bg-white px-3.5 py-2.5 text-base text-black focus:border-suppli focus:outline-none focus:ring-2 focus:ring-suppli">
            </div>

            <div>
              <label for="password" class="mb-1.5 block text-sm font-semibold">Contraseña</label>
              <div class="relative">
                <input type="password" id="password" name="password" required minlength="8"
                       autocomplete="new-password" aria-describedby="password-hint"
                       class="w-full rounded-md border border-suppli/60 bg-white py-2.5 pl-3.5 pr-24 text-base text-black focus:border-suppli focus:outline-none focus:ring-2 focus:ring-suppli">
                <button type="button" data-toggle-password="password"
                        aria-controls="password" aria-pressed="false"
                        class="absolute inset-y-0 right-0 rounded-r-md px-3.5 text-sm font-semibold text-suppli hover:text-suppli-dark focus-visible:outline focus-visible:outline-2 focus-visible:outline-suppli">
                  Mostrar
                </button>
              </div>
              <p id="password-hint" class="mt-1.5 text-sm text-suppli-dark/80">Mínimo 8 caracteres.</p>
            </div>

            <div>
              <label for="password_confirmation" class="mb-1.5 block text-sm font-semibold">Repite la contraseña</label>
              <div class="relative">
                <input type="password" id="password_confirmation" name="password_confirmation" required minlength="8"
                       autocomplete="new-password" aria-describedby="match-error"
                       class="w-full rounded-md border border-suppli/60 bg-white py-2.5 pl-3.5 pr-24 text-base text-black focus:border-suppli focus:outline-none focus:ring-2 focus:ring-suppli">
                <button type="button" data-toggle-password="password_confirmation"
                        aria-controls="password_confirmation" aria-pressed="false"
                        class="absolute inset-y-0 right-0 rounded-r-md px-3.5 text-sm font-semibold text-suppli hover:text-suppli-dark focus-visible:outline focus-visible:outline-2 focus-visible:outline-suppli">
                  Mostrar
                </button>
              </div>
              <p id="match-error" class="mt-1.5 hidden text-sm text-red-700" role="alert">Las contraseñas no coinciden.</p>
            </div>

            <button type="submit"
                    class="w-full rounded-md bg-suppli px-4 py-3 font-semibold text-white transition-colors hover:bg-suppli-dark focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-suppli">
              Crear cuenta
            </button>
          </form>

          <p class="mt-8 text-sm text-suppli-dark/80">
            ¿Ya tienes cuenta?
            <a href="/login" class="font-semibold text-suppli underline underline-offset-2 hover:text-suppli-dark focus-visible:outline focus-visible:outline-2 focus-visible:outline-suppli">Iniciar sesión</a>
          </p>
        </div>
      </main>

      <!-- PIE: centrado sobre el ancho total -->
      <footer class="my-6 justify-self-center lg:absolute lg:bottom-6 lg:left-1/2 lg:my-0 lg:-translate-x-1/2">
        <p class="w-max max-w-[calc(100vw-2rem)] rounded-full bg-suppli-cream px-4 py-1.5 text-center text-xs text-suppli-dark shadow-md ring-1 ring-suppli/25 sm:text-[0.8rem] lg:whitespace-nowrap">
          © IB Consultoras. Todos los derechos reservados.
        </p>
      </footer>

    </div>

    <script>
      // Mostrar / ocultar contraseña
      document.querySelectorAll('[data-toggle-password]').forEach(function (btn) {
        btn.addEventListener('click', function () {
          var input = document.getElementById(btn.dataset.togglePassword);
          var show = input.type === 'password';
          input.type = show ? 'text' : 'password';
          btn.textContent = show ? 'Ocultar' : 'Mostrar';
          btn.setAttribute('aria-pressed', show ? 'true' : 'false');
        });
      });

      // Aviso en vivo si las contraseñas no coinciden (el servidor también lo valida)
      (function () {
        var pass = document.getElementById('password');
        var confirm = document.getElementById('password_confirmation');
        var msg = document.getElementById('match-error');

        function check() {
          var mismatch = confirm.value !== '' && pass.value !== confirm.value;
          msg.classList.toggle('hidden', !mismatch);
          confirm.setCustomValidity(mismatch ? 'Las contraseñas no coinciden.' : '');
        }
        pass.addEventListener('input', check);
        confirm.addEventListener('input', check);
      })();
    </script>
  </body>
</html>
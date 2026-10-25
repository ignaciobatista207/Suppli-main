<?php
/**
 * src/views/auth/login.php
 *
 * Variables opcionales que puede recibir desde el controlador:
 *   $error  string|null  Mensaje del intento anterior (ej. "Correo o contraseña incorrectos.")
 *   $old    array        Valores previos del formulario (ej. ['email' => 'ana@mail.com'])
 */
$error = $error ?? null;
$old = $old ?? [];
?>
<!DOCTYPE html>
<html lang="es">
  <head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Iniciar sesión | Suppli</title>

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
                dark: '#000000',
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
        <img src="/images-suppli/editSuppli1.jpg" alt=""
             class="absolute inset-0 -z-20 h-full w-full object-cover saturate-0">
        <div class="absolute inset-0 -z-10 bg-suppli/80" aria-hidden="true"></div>

        <a href="/"
           class="inline-block rounded focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-suppli-cream lg:absolute lg:left-12 lg:top-12">
          <img src="/images-suppli/supplipngCrema.png" alt="Suppli" class="h-auto w-32 lg:w-44">
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

          <h1 class="font-display text-3xl font-extrabold tracking-tight">Iniciar sesión</h1>
          <p class="mt-2 text-suppli-dark/80">Entra para ver tus repositorios y abrir la caja.</p>

          <?php if ($error): ?>
            <div role="alert" class="mt-6 rounded-md border border-red-300 bg-red-50 px-4 py-3 text-sm text-red-800">
              <?= html($error) ?>
            </div>
          <?php endif; ?>

          <form action="/login" method="POST" class="mt-8 space-y-5">

            <div>
              <label for="email" class="mb-1.5 block text-sm font-semibold">Correo electrónico</label>
              <input type="email" id="email" name="email" required autofocus
                     autocomplete="email" maxlength="150"
                     value="<?= html($old['email'] ?? '') ?>"
                     class="w-full rounded-md border border-suppli/60 bg-white px-3.5 py-2.5 text-base text-black focus:border-suppli focus:outline-none focus:ring-2 focus:ring-suppli">
            </div>

            <div>
              <label for="password" class="mb-1.5 block text-sm font-semibold">Contraseña</label>
              <div class="relative">
                <input type="password" id="password" name="password" required
                       autocomplete="current-password"
                       class="w-full rounded-md border border-suppli/60 bg-white py-2.5 pl-3.5 pr-24 text-base text-black focus:border-suppli focus:outline-none focus:ring-2 focus:ring-suppli">
                <button type="button" data-toggle-password="password"
                        aria-controls="password" aria-pressed="false"
                        class="absolute inset-y-0 right-0 rounded-r-md px-3.5 text-sm font-semibold text-suppli hover:text-suppli-dark focus-visible:outline focus-visible:outline-2 focus-visible:outline-suppli">
                  Mostrar
                </button>
              </div>
            </div>

            <button type="submit"
                    class="w-full rounded-md bg-suppli px-4 py-3 font-semibold text-white transition-colors hover:bg-suppli-dark focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-suppli">
              Iniciar sesión
            </button>
          </form>

          <p class="mt-8 text-sm text-suppli-dark/80">
            ¿Todavía no tienes cuenta?
            <a href="/register" class="font-semibold text-suppli underline underline-offset-2 hover:text-suppli-dark focus-visible:outline focus-visible:outline-2 focus-visible:outline-suppli">Crear cuenta</a>
          </p>
        </div>
      </main>

      <!-- PIE: centrado sobre el ancho total -->
      <footer class="my-6 justify-self-center lg:absolute lg:bottom-6 lg:left-1/2 lg:my-0 lg:-translate-x-1/2">
        <p class="w-max max-w-[calc(100vw-2rem)] rounded-full bg-suppli-cream px-4 py-1.5 text-center text-xs text-suppli-dark shadow-md ring-1 ring-suppli/25 sm:text-[0.8rem] lg:whitespace-nowrap">
          © IB Consultora. Todos los derechos reservados.
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
    </script>
  </body>
</html>
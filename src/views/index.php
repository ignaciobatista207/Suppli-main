<?php
/**
 * src/views/index.php
 *
 * Landing de Suppli. Documento HTML completo: se renderiza con
 * $renderer->render() sin pasar por view()/layouts/base.php.
 */
?>
<!DOCTYPE html>
<html lang="es">
  <head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Suppli | Tu stock y tu caja, en el mismo lugar</title>

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
        <img src="/images-suppli/editSuppli1.png" alt=""
             class="absolute inset-0 -z-20 h-full w-full object-cover">
        <div class="absolute inset-0 -z-10 bg-suppli/80" aria-hidden="true"></div>

        <a href="/"
           class="inline-block rounded focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-suppli-cream lg:absolute lg:left-12 lg:top-12">
          <img src="/images-suppli/pngsuppliCrema.png" alt="Suppli" class="h-auto w-32 lg:w-44">
        </a>

        <div class="mt-6 max-w-xl lg:mt-0">
          <h1 class="font-display text-3xl font-extrabold leading-tight tracking-tight text-suppli-cream sm:text-4xl lg:text-5xl">
            Tu stock y tu caja, en el mismo lugar
          </h1>
          <p class="mt-3 text-base text-suppli-cream/95 lg:mt-5 lg:text-xl">
            Con Suppli, siempre sabes qué tienes a mano
          </p>
        </div>
      </header>

      <!-- MITAD DERECHA: contenido -->
      <main class="flex items-center justify-center px-6 py-12 lg:pb-24">
        <div class="w-full max-w-sm">
          <h2 class="font-display text-3xl font-extrabold tracking-tight">Bienvenido a Suppli</h2>
          <p class="mt-2 text-suppli-dark/80">
            Inicia sesión o crea tu cuenta para empezar a ordenar tu negocio.
          </p>

          <div class="mt-8 space-y-3">
            <a href="/login"
               class="block w-full rounded-md bg-suppli px-4 py-3 text-center font-semibold text-white transition-colors hover:bg-suppli-dark focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-suppli">
              Iniciar sesión
            </a>
            <a href="/register"
               class="block w-full rounded-md border-2 border-suppli bg-transparent px-4 py-3 text-center font-semibold text-suppli transition-colors hover:bg-suppli hover:text-white focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-suppli">
              Registrarse
            </a>
          </div>
        </div>
      </main>

      <!-- PIE: centrado sobre el ancho total -->
      <footer class="my-6 justify-self-center lg:absolute lg:bottom-6 lg:left-1/2 lg:my-0 lg:-translate-x-1/2">
        <p class="w-max max-w-[calc(100vw-2rem)] rounded-full bg-suppli-cream px-4 py-1.5 text-center text-xs text-suppli-dark shadow-md ring-1 ring-suppli/25 sm:text-[0.8rem] lg:whitespace-nowrap">
          © IB Consultoras. Todos los derechos reservados.
        </p>
      </footer>

    </div>
  </body>
</html>
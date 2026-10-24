<?php
/**
 * src/views/auth/registro.php
 *
 * Variables opcionales que puede recibir desde el controlador:
 *   $errors  string[]  Lista de errores de validación (ej. ["Ese correo ya está registrado."])
 *   $old     array     Valores previos del formulario (ej. ['name' => 'Ana', 'email' => 'ana@mail.com'])
 */
$e      = static fn($v): string => htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
$errors = $errors ?? [];
$old    = $old ?? [];
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
                            ink:  '#12372A',
                            leaf: '#1F6F54',
                            tag:  '#FFD23F',
                            mist: '#F1F5F2',
                            line: '#D5DED8'
                        }
                    },
                    fontFamily: {
                        display: ['"Bricolage Grotesque"', 'system-ui', 'sans-serif'],
                        sans:    ['"DM Sans"', 'system-ui', 'sans-serif']
                    }
                }
            }
        };
    </script>
</head>
<body class="min-h-screen bg-suppli-mist font-sans text-suppli-ink antialiased">

<div class="grid min-h-screen lg:grid-cols-2">

    <!-- Panel de marca (solo escritorio) -->
    <aside class="hidden flex-col justify-between bg-suppli-ink p-12 text-white lg:flex">
        <a href="/" class="font-display text-2xl font-extrabold tracking-tight">Suppli</a>

        <div>
            <!-- Etiqueta de góndola de ejemplo -->
            <div class="w-80 -rotate-2 rounded-md bg-suppli-tag p-5 text-suppli-ink shadow-[6px_6px_0_0_rgba(0,0,0,.25)]">
                <p class="font-display text-xl font-semibold leading-tight">Detergente 750 ml</p>
                <p class="mt-1 font-display text-5xl font-extrabold tracking-tight">$ 2.350</p>
                <p class="mt-3 text-sm font-medium">Góndola: Limpieza</p>
                <p class="text-sm font-medium">Stock: 12 unidades</p>
                <div class="mt-4 h-10"
                     aria-hidden="true"
                     style="background: repeating-linear-gradient(90deg, #12372A 0 2px, transparent 2px 4px, #12372A 4px 5px, transparent 5px 8px);"></div>
            </div>

            <h2 class="mt-12 max-w-md font-display text-4xl font-extrabold leading-tight tracking-tight">
                Ordena tu negocio desde el primer día.
            </h2>
            <p class="mt-4 max-w-md text-lg text-white/75">
                Crea tu cuenta, arma tu primer repositorio y empieza a cargar productos. Si ya existe uno, puedes unirte con su contraseña.
            </p>
        </div>
    </aside>

    <!-- Formulario -->
    <main class="flex items-center justify-center px-6 py-12">
        <div class="w-full max-w-sm">

            <a href="/" class="mb-10 block font-display text-2xl font-extrabold tracking-tight lg:hidden">Suppli</a>

            <h1 class="font-display text-3xl font-extrabold tracking-tight">Crear cuenta</h1>
            <p class="mt-2 text-suppli-ink/70">Solo necesitas un correo y una contraseña.</p>

            <?php if (!empty($errors)): ?>
                <div role="alert" class="mt-6 rounded-md border border-red-300 bg-red-50 px-4 py-3 text-sm text-red-800">
                    <p class="font-semibold">Revisa estos datos:</p>
                    <ul class="mt-1 list-disc space-y-0.5 pl-5">
                        <?php foreach ($errors as $msg): ?>
                            <li><?= $e($msg) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <form action="/register" method="POST" class="mt-8 space-y-5" novalidate>

                <div>
                    <label for="name" class="mb-1.5 block text-sm font-semibold">Nombre</label>
                    <input type="text" id="name" name="name" required autofocus
                           autocomplete="name" maxlength="100"
                           value="<?= $e($old['name'] ?? '') ?>"
                           class="w-full rounded-md border border-suppli-line bg-white px-3.5 py-2.5 text-base focus:border-suppli-leaf focus:outline-none focus:ring-2 focus:ring-suppli-leaf/30">
                </div>

                <div>
                    <label for="email" class="mb-1.5 block text-sm font-semibold">Correo electrónico</label>
                    <input type="email" id="email" name="email" required
                           autocomplete="email" maxlength="150"
                           value="<?= $e($old['email'] ?? '') ?>"
                           class="w-full rounded-md border border-suppli-line bg-white px-3.5 py-2.5 text-base focus:border-suppli-leaf focus:outline-none focus:ring-2 focus:ring-suppli-leaf/30">
                </div>

                <div>
                    <label for="password" class="mb-1.5 block text-sm font-semibold">Contraseña</label>
                    <div class="relative">
                        <input type="password" id="password" name="password" required minlength="8"
                               autocomplete="new-password" aria-describedby="password-hint"
                               class="w-full rounded-md border border-suppli-line bg-white py-2.5 pl-3.5 pr-24 text-base focus:border-suppli-leaf focus:outline-none focus:ring-2 focus:ring-suppli-leaf/30">
                        <button type="button" data-toggle-password="password" aria-pressed="false"
                                class="absolute inset-y-0 right-0 px-3.5 text-sm font-semibold text-suppli-leaf hover:text-suppli-ink focus-visible:outline focus-visible:outline-2 focus-visible:outline-suppli-leaf">
                            Mostrar
                        </button>
                    </div>
                    <p id="password-hint" class="mt-1.5 text-sm text-suppli-ink/60">Mínimo 8 caracteres.</p>
                </div>

                <div>
                    <label for="password_confirmation" class="mb-1.5 block text-sm font-semibold">Repite la contraseña</label>
                    <div class="relative">
                        <input type="password" id="password_confirmation" name="password_confirmation" required minlength="8"
                               autocomplete="new-password"
                               class="w-full rounded-md border border-suppli-line bg-white py-2.5 pl-3.5 pr-24 text-base focus:border-suppli-leaf focus:outline-none focus:ring-2 focus:ring-suppli-leaf/30">
                        <button type="button" data-toggle-password="password_confirmation" aria-pressed="false"
                                class="absolute inset-y-0 right-0 px-3.5 text-sm font-semibold text-suppli-leaf hover:text-suppli-ink focus-visible:outline focus-visible:outline-2 focus-visible:outline-suppli-leaf">
                            Mostrar
                        </button>
                    </div>
                    <p id="match-error" class="mt-1.5 hidden text-sm text-red-700" role="alert">Las contraseñas no coinciden.</p>
                </div>

                <button type="submit"
                        class="w-full rounded-md bg-suppli-leaf px-4 py-3 font-semibold text-white transition-colors hover:bg-suppli-ink focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-suppli-leaf">
                    Crear cuenta
                </button>
            </form>

            <p class="mt-8 text-sm text-suppli-ink/70">
                ¿Ya tienes cuenta?
                <a href="/login" class="font-semibold text-suppli-leaf underline underline-offset-2 hover:text-suppli-ink">Iniciar sesión</a>
            </p>
        </div>
    </main>
</div>

<script>
    // Mostrar / ocultar contraseña
    document.querySelectorAll('[data-toggle-password]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var input = document.getElementById(btn.dataset.togglePassword);
            var show  = input.type === 'password';
            input.type = show ? 'text' : 'password';
            btn.textContent = show ? 'Ocultar' : 'Mostrar';
            btn.setAttribute('aria-pressed', show ? 'true' : 'false');
        });
    });

    // Aviso en vivo si las contraseñas no coinciden (el servidor también lo valida)
    (function () {
        var pass    = document.getElementById('password');
        var confirm = document.getElementById('password_confirmation');
        var msg     = document.getElementById('match-error');

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
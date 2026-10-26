<!doctype html>
<html lang="es" data-theme="suppli">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title><?= html($title ?? "Suppli") ?></title>

    <!-- Tema: se aplica ANTES de pintar para evitar parpadeo -->
    <script>
      (function () {
        var theme = "suppli";
        try {
          var saved = localStorage.getItem("suppli-theme");
          if (saved === "suppli" || saved === "bn") theme = saved;
        } catch (err) {}
        document.documentElement.setAttribute("data-theme", theme);
      })();
    </script>

    <!-- Fuentes -->
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link href="https://fonts.googleapis.com/css2?family=Bricolage+Grotesque:opsz,wght@12..96,600;12..96,800&family=DM+Sans:wght@400;500;600&family=Be+Vietnam+Pro:wght@400;500;600;700&display=swap" rel="stylesheet" />

    <!-- Tokens de tema (canales RGB para poder usar opacidad: text-ink/80) -->
    <style>
      :root, :root[data-theme="suppli"] {
        --c-canvas: 245 240 232;   /* crema */
        --c-bar: 41 67 112;        /* azul Suppli */
        --c-onbar: 245 240 232;
        --c-ink: 41 67 112;
        --c-accent: 41 67 112;
        --c-accenth: 31 52 90;     /* azul más oscuro para hover */
        --c-onaccent: 245 240 232;
        --c-field: 245 240 232;
        --c-card: 255 255 255;
        --c-danger: 179 38 30;
      }
      :root[data-theme="bn"] {
        --c-canvas: 255 255 255;
        --c-bar: 0 0 0;
        --c-onbar: 255 255 255;
        --c-ink: 0 0 0;
        --c-accent: 0 0 0;
        --c-accenth: 38 38 38;
        --c-onaccent: 255 255 255;
        --c-field: 255 255 255;
        --c-card: 255 255 255;
        --c-danger: 179 38 30;
      }
      /* Íconos PNG: en blanco y negro se pasan a escala de grises */
      :root[data-theme="bn"] .icon-themed { filter: grayscale(1) brightness(.2); }
      /* Íconos sobre botones de color: se vuelven claros */
      .icon-on-accent { filter: brightness(0) invert(1); }
    </style>

    <!-- Tailwind por CDN (desarrollo) -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
      tailwind.config = {
        theme: {
          extend: {
            colors: {
              canvas:   "rgb(var(--c-canvas) / <alpha-value>)",
              bar:      "rgb(var(--c-bar) / <alpha-value>)",
              onbar:    "rgb(var(--c-onbar) / <alpha-value>)",
              ink:      "rgb(var(--c-ink) / <alpha-value>)",
              accent:   "rgb(var(--c-accent) / <alpha-value>)",
              accenth:  "rgb(var(--c-accenth) / <alpha-value>)",
              onaccent: "rgb(var(--c-onaccent) / <alpha-value>)",
              field:    "rgb(var(--c-field) / <alpha-value>)",
              card:     "rgb(var(--c-card) / <alpha-value>)",
              danger:   "rgb(var(--c-danger) / <alpha-value>)"
            },
            fontFamily: {
              display: ['"Bricolage Grotesque"', "system-ui", "sans-serif"],
              sans:    ['"Be Vietnam Pro"', "system-ui", "sans-serif"],
              dm:      ['"DM Sans"', "system-ui", "sans-serif"]
            }
          }
        }
      };
    </script>
  </head>
  <body class="bg-canvas font-sans text-ink antialiased">
    <?= $content ?? "" ?>
  </body>
</html>
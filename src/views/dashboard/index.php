<main>
  <h1>Hola, <?= html($user["name"]) ?></h1>
  <p>Sesión iniciada correctamente. Esta pantalla es provisoria.</p>

  <form action="/logout" method="POST">
    <button type="submit">Cerrar sesión</button>
  </form>
</main>
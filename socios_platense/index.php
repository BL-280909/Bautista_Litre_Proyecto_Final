<?php
$titulo = 'Inicio';
require_once __DIR__ . '/includes/cabecera.php';
?>
</main>
<section class="portada">
  <div class="contenedor">
    <div>
      <h1>Página de socios del Club Atlético Platense</h1>
      <p>Asociate, ingresá y consultá el padrón de socios del calamar.</p>
      <div class="acciones">
        <?php if (hay_sesion()): ?>
          <a class="boton" href="panel.php">Ir a mi panel</a>
        <?php else: ?>
          <a class="boton" href="registro.php">Crear cuenta</a>
          <a class="boton sec" href="login.php">Ingresar</a>
        <?php endif; ?>
        <a class="boton sec" href="asociarme.php">Quiero asociarme</a>
        <a class="boton sec" href="listado.php">Ver socios</a>
      </div>
    </div>
    <img src="img/escudo.png" alt="Escudo del Club Atlético Platense">
  </div>
</section>
<main class="contenedor">
  <div class="tres">
    <div><h3>Tu cuenta</h3><p>Registrate con tu DNI y tu correo. La contraseña se guarda cifrada.</p></div>
    <div><h3>El padrón</h3><p>Consultá número de socio, categoría y foto de cada socio.</p></div>
    <div><h3>Administración</h3><p>Los administradores cargan socios y gestionan cuotas y usuarios.</p></div>
  </div>
<?php require_once __DIR__ . '/includes/pie.php'; ?>

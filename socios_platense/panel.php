<?php
require_once __DIR__ . '/conexion.php';
require_once __DIR__ . '/includes/funciones.php';
requerir_login();

$usuario = false;
$socio = false;
$pendientes = [];
$pagadas = [];
$errorCuotas = '';

try {
    $stmt = $conexion->prepare('SELECT nombre, dni FROM usuarios WHERE id = :id AND archivado_en IS NULL LIMIT 1');
    $stmt->execute(['id' => $_SESSION['usuario_id']]);
    $usuario = $stmt->fetch();
    if ($usuario === false) { // cuenta archivada o borrada
        $_SESSION = [];
        session_destroy();
        header('Location: login.php');
        exit;
    }

    // El usuario se vincula con el padrón por su DNI
    $stmt = $conexion->prepare('SELECT id, numero_socio, categoria FROM socios WHERE dni = :dni LIMIT 1');
    $stmt->execute(['dni' => $usuario['dni']]);
    $socio = $stmt->fetch();

    if ($socio !== false) {
        $stmt = $conexion->prepare("SELECT periodo, monto FROM cuotas
            WHERE socio_id = :id AND estado = 'pendiente' ORDER BY periodo ASC");
        $stmt->execute(['id' => $socio['id']]);
        $pendientes = $stmt->fetchAll();

        $stmt = $conexion->prepare("SELECT periodo, monto, fecha_pago FROM cuotas
            WHERE socio_id = :id AND estado = 'pagada' ORDER BY periodo DESC LIMIT 12");
        $stmt->execute(['id' => $socio['id']]);
        $pagadas = $stmt->fetchAll();
    }
} catch (PDOException $e) {
    error_log($e->getMessage());
    $errorCuotas = 'No se pudo consultar el estado de tus cuotas.';
}

$total = array_sum(array_column($pendientes, 'monto'));
$mesAnio = fn(string $p): string => substr($p, 5, 2) . '/' . substr($p, 0, 4);
$pesos = fn($n): string => '$' . number_format((float)$n, 2, ',', '.');

$titulo = 'Mi panel';
require_once __DIR__ . '/includes/cabecera.php';
?>
<div class="pagina">
  <h1>Hola, <?= escapar($usuario['nombre'] ?? '') ?></h1>
  <?php if (isset($_GET['bienvenida']) && $socio !== false): ?>
    <p class="aviso" role="status"><strong>¡Bienvenido al club!</strong> Ya sos socio N.º <?= escapar($socio['numero_socio']) ?>.</p>
  <?php endif; ?>

  <?php if ($errorCuotas !== ''): ?>
    <p class="error" role="alert"><?= escapar($errorCuotas) ?></p>
  <?php elseif ($socio === false): ?>
    <p class="aviso">Todavía no sos socio del club. Asociate y vas a ver acá tu número de socio y tus cuotas.</p>
    <div class="acciones"><a class="boton" href="asociarme.php">Quiero asociarme</a></div>
  <?php elseif (empty($pendientes)): ?>
    <div class="cuota-card">
      <h2>Socio N.º <?= escapar($socio['numero_socio']) ?> · <?= escapar($socio['categoria']) ?></h2>
      <p><span class="estado">Al día</span> No tenés cuotas pendientes.</p>
    </div>
  <?php else: ?>
    <div class="cuota-card debe" role="status">
      <h2>Socio N.º <?= escapar($socio['numero_socio']) ?> · <?= escapar($socio['categoria']) ?></h2>
      <p><strong>Tenés <?= count($pendientes) ?> <?= count($pendientes) === 1 ? 'cuota pendiente' : 'cuotas pendientes' ?></strong>
         por un total de <strong><?= escapar($pesos($total)) ?></strong>.</p>
    </div>
    <div class="tabla-scroll" style="max-width:40rem">
      <table class="chica">
        <thead><tr><th>Período</th><th>Monto</th><th>Estado</th></tr></thead>
        <tbody>
        <?php foreach ($pendientes as $c): ?>
          <tr><td><?= escapar($mesAnio($c['periodo'])) ?></td><td><?= escapar($pesos($c['monto'])) ?></td><td><span class="estado debe">Pendiente</span></td></tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>

  <?php if (!empty($pagadas)): ?>
    <h2 style="margin-top:2rem">Pagos realizados</h2>
    <div class="tabla-scroll" style="max-width:40rem">
      <table class="chica">
        <thead><tr><th>Período</th><th>Monto</th><th>Fecha de pago</th></tr></thead>
        <tbody>
        <?php foreach ($pagadas as $c): ?>
          <tr><td><?= escapar($mesAnio($c['periodo'])) ?></td><td><?= escapar($pesos($c['monto'])) ?></td>
              <td><?= escapar($c['fecha_pago'] ? date('d/m/Y', strtotime($c['fecha_pago'])) : '-') ?></td></tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>

  <div class="acciones" style="margin-top:1.5rem">
    <a class="boton" href="perfil.php">Modificar mis datos</a>
    <a class="boton" href="listado.php">Ver socios</a>
    <?php if (es_admin()): ?>
      <a class="boton" href="admin_socios.php">Cargar socio</a>
      <a class="boton" href="admin_cuotas.php">Cuotas</a>
      <a class="boton sec" href="admin_usuarios.php">Gestionar usuarios</a>
    <?php endif; ?>
    <a class="boton sec" href="cerrar_sesion.php">Cerrar sesión</a>
  </div>
</div>
<?php require_once __DIR__ . '/includes/pie.php'; ?>

<?php
require_once __DIR__ . '/conexion.php';
require_once __DIR__ . '/includes/funciones.php';
requerir_admin();

$errores = [];
$mensaje = '';
$periodo = date('Y-m');
$monto = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $accion = $_POST['accion'] ?? '';

    try {
        if ($accion === 'generar') {
            $periodo = trim($_POST['periodo'] ?? '');
            $monto = trim($_POST['monto'] ?? '');
            $montoNum = filter_var(str_replace(',', '.', $monto), FILTER_VALIDATE_FLOAT);

            if (!preg_match('/^[0-9]{4}-(0[1-9]|1[0-2])$/', $periodo)) { $errores[] = 'El período no es válido.'; }
            if ($montoNum === false || $montoNum <= 0) { $errores[] = 'El monto debe ser un número mayor a 0.'; }

            if (empty($errores)) {
                // IGNORE evita duplicar la cuota de un socio en el mismo mes
                $stmt = $conexion->prepare('INSERT IGNORE INTO cuotas (socio_id, periodo, monto)
                    SELECT id, :periodo, :monto FROM socios');
                $stmt->execute(['periodo' => $periodo, 'monto' => $montoNum]);
                $mensaje = 'Se generaron ' . $stmt->rowCount() . ' cuotas para ' . substr($periodo, 5, 2) . '/' . substr($periodo, 0, 4) . '.';
                $monto = '';
            }
        } elseif ($accion === 'pagar') {
            $id = filter_var($_POST['id'] ?? '', FILTER_VALIDATE_INT);
            if ($id === false) {
                $errores[] = 'Cuota no válida.';
            } else {
                $stmt = $conexion->prepare("UPDATE cuotas SET estado = 'pagada', fecha_pago = CURDATE()
                    WHERE id = :id AND estado = 'pendiente'");
                $stmt->execute(['id' => $id]);
                $mensaje = 'Cuota marcada como pagada.';
            }
        }
    } catch (PDOException $e) {
        error_log($e->getMessage());
        $errores[] = 'Ocurrió un error al guardar los cambios.';
    }
}

try {
    $pendientes = $conexion->query("SELECT c.id, c.periodo, c.monto, s.numero_socio, s.nombre, s.apellido
        FROM cuotas c JOIN socios s ON s.id = c.socio_id
        WHERE c.estado = 'pendiente'
        ORDER BY c.periodo DESC, s.apellido ASC")->fetchAll();
} catch (PDOException $e) {
    error_log($e->getMessage());
    http_response_code(500);
    exit('No se pudieron consultar las cuotas. ¿Ejecutaste sql/cuotas.sql?');
}

$titulo = 'Cuotas';
require_once __DIR__ . '/includes/cabecera.php';
?>
<div class="pagina">
  <h1>Cuotas</h1>
  <?php if ($mensaje !== ''): ?><p class="aviso"><?= escapar($mensaje) ?></p><?php endif; ?>
  <?php if (!empty($errores)): ?>
    <div class="error" role="alert"><ul><?php foreach ($errores as $e): ?><li><?= escapar($e) ?></li><?php endforeach; ?></ul></div>
  <?php endif; ?>

  <h2>Generar cuota del mes para todos los socios</h2>
  <form class="formulario en-linea" action="admin_cuotas.php" method="POST">
    <input type="hidden" name="accion" value="generar">
    <div><label for="periodo">Período</label>
      <input type="month" id="periodo" name="periodo" value="<?= escapar($periodo) ?>" required></div>
    <div><label for="monto">Monto ($)</label>
      <input type="text" id="monto" name="monto" inputmode="decimal" value="<?= escapar($monto) ?>" required></div>
    <button type="submit">Generar</button>
  </form>

  <h2 style="margin-top:2.5rem">Cuotas pendientes</h2>
  <p class="total"><strong>Total:</strong> <?= count($pendientes) ?></p>
  <?php if (empty($pendientes)): ?>
    <p class="aviso">No hay cuotas pendientes.</p>
  <?php else: ?>
  <div class="tabla-scroll">
    <table>
      <thead><tr><th>N.º socio</th><th>Socio</th><th>Período</th><th>Monto</th><th>Acción</th></tr></thead>
      <tbody>
      <?php foreach ($pendientes as $c): ?>
        <tr>
          <td><?= escapar($c['numero_socio']) ?></td>
          <td><?= escapar($c['apellido'] . ', ' . $c['nombre']) ?></td>
          <td><?= escapar(substr($c['periodo'], 5, 2) . '/' . substr($c['periodo'], 0, 4)) ?></td>
          <td>$<?= escapar(number_format((float)$c['monto'], 2, ',', '.')) ?></td>
          <td>
            <form action="admin_cuotas.php" method="POST">
              <input type="hidden" name="accion" value="pagar">
              <input type="hidden" name="id" value="<?= escapar($c['id']) ?>">
              <button type="submit">Marcar pagada</button>
            </form>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php endif; ?>
</div>
<?php require_once __DIR__ . '/includes/pie.php'; ?>

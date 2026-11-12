<?php
require_once __DIR__ . '/conexion.php';
require_once __DIR__ . '/includes/funciones.php';

try {
    $sql = 'SELECT id, numero_socio, nombre, apellido, dni, email, categoria, foto, fecha_registro
            FROM socios
            ORDER BY numero_socio ASC';
    $socios = $conexion->query($sql)->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log($e->getMessage());
    http_response_code(500);
    exit('No se pudieron consultar los socios.');
}

$titulo = 'Socios';
require_once __DIR__ . '/includes/cabecera.php';
?>
<div class="pagina">
  <h1>Socios del club</h1>
  <p class="total"><strong>Total:</strong> <?= count($socios) ?></p>

  <?php if (empty($socios)): ?>
    <p class="aviso">Todavía no hay socios cargados.
      <?php if (es_admin()): ?><a href="admin_socios.php">Cargá el primero</a>.<?php endif; ?></p>
  <?php else: ?>
  <div class="tabla-scroll">
    <table>
      <thead><tr><th>Foto</th><th>N.º</th><th>Nombre</th><th>Apellido</th><th>DNI</th><th>Categoría</th><th>Alta</th></tr></thead>
      <tbody>
      <?php foreach ($socios as $socio): ?>
        <tr>
          <td>
            <?php if (!empty($socio['foto'])): ?>
              <img class="foto" src="<?= escapar($socio['foto']) ?>" alt="Foto de <?= escapar($socio['nombre'] . ' ' . $socio['apellido']) ?>">
            <?php else: ?>
              <span class="sin-foto" aria-hidden="true"><?= escapar(mb_strtoupper(mb_substr($socio['nombre'], 0, 1) . mb_substr($socio['apellido'], 0, 1))) ?></span>
            <?php endif; ?>
          </td>
          <td><?= escapar($socio['numero_socio']) ?></td>
          <td><?= escapar($socio['nombre']) ?></td>
          <td><?= escapar($socio['apellido']) ?></td>
          <td><?= escapar($socio['dni']) ?></td>
          <td><?= escapar($socio['categoria']) ?></td>
          <td><?= escapar(date('d/m/Y', strtotime($socio['fecha_registro']))) ?></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php endif; ?>
</div>
<?php require_once __DIR__ . '/includes/pie.php'; ?>

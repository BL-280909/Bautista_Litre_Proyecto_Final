<?php
require_once __DIR__ . '/conexion.php';
require_once __DIR__ . '/includes/funciones.php';
requerir_admin(); // antes de enviar cualquier HTML

$errores = [];
$mensaje = '';
$nombre = $apellido = $dni = $email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = trim($_POST['nombre'] ?? '');
    $apellido = trim($_POST['apellido'] ?? '');
    $dni = trim($_POST['dni'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $rol = $_POST['rol'] ?? 'usuario';

    if ($nombre === '' || $apellido === '') { $errores[] = 'Nombre y apellido son obligatorios.'; }
    if (!preg_match('/^[0-9]{7,8}$/', $dni)) { $errores[] = 'El DNI debe tener 7 u 8 números.'; }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) { $errores[] = 'Ingresá un correo válido.'; }
    if (strlen($password) < 8) { $errores[] = 'La contraseña debe tener al menos 8 caracteres.'; }
    if (!in_array($rol, ['usuario', 'admin'], true)) { $errores[] = 'Rol no válido.'; }

    if (empty($errores)) {
        try {
            $stmt = $conexion->prepare('INSERT INTO usuarios (nombre, apellido, dni, email, password_hash, rol)
                VALUES (:nombre, :apellido, :dni, :email, :password_hash, :rol)');
            $stmt->execute([
                'nombre' => $nombre, 'apellido' => $apellido, 'dni' => $dni, 'email' => $email,
                'password_hash' => password_hash($password, PASSWORD_DEFAULT), 'rol' => $rol,
            ]);
            $mensaje = 'Usuario creado correctamente.';
            $nombre = $apellido = $dni = $email = '';
        } catch (PDOException $e) {
            if ($e->getCode() === '23000') {
                $errores[] = 'Ya existe un usuario con ese DNI o correo.';
            } else {
                error_log($e->getMessage());
                $errores[] = 'Ocurrió un error al guardar el usuario.';
            }
        }
    }
}

$usuarios = $conexion->query('SELECT id, nombre, apellido, dni, email, rol, creado_en
    FROM usuarios WHERE archivado_en IS NULL ORDER BY id DESC')->fetchAll();

$titulo = 'Usuarios';
require_once __DIR__ . '/includes/cabecera.php';
?>
<div class="pagina">
  <h1>Usuarios</h1>
  <?php if ($mensaje !== ''): ?><p class="aviso"><?= escapar($mensaje) ?></p><?php endif; ?>
  <?php if (!empty($errores)): ?>
    <div class="error" role="alert"><strong>Revisá estos datos:</strong>
      <ul><?php foreach ($errores as $e): ?><li><?= escapar($e) ?></li><?php endforeach; ?></ul>
    </div>
  <?php endif; ?>

  <h2>Crear usuario</h2>
  <form class="formulario" action="admin_usuarios.php" method="POST">
    <label for="nombre">Nombre</label><input type="text" id="nombre" name="nombre" maxlength="80" value="<?= escapar($nombre) ?>" required>
    <label for="apellido">Apellido</label><input type="text" id="apellido" name="apellido" maxlength="80" value="<?= escapar($apellido) ?>" required>
    <label for="dni">DNI</label><input type="text" id="dni" name="dni" inputmode="numeric" pattern="[0-9]{7,8}" maxlength="8" value="<?= escapar($dni) ?>" required>
    <label for="email">Correo electrónico</label><input type="email" id="email" name="email" maxlength="254" value="<?= escapar($email) ?>" required>
    <label for="password">Contraseña</label><input type="password" id="password" name="password" minlength="8" required>
    <label for="rol">Rol</label>
    <select id="rol" name="rol"><option value="usuario">Usuario</option><option value="admin">Administrador</option></select>
    <button type="submit">Crear usuario</button>
  </form>

  <h2 style="margin-top:2.5rem">Usuarios registrados</h2>
  <p class="total"><strong>Total:</strong> <?= count($usuarios) ?></p>
  <?php if (empty($usuarios)): ?>
    <p>No hay usuarios registrados.</p>
  <?php else: ?>
  <div class="tabla-scroll">
    <table>
      <thead><tr><th>ID</th><th>Nombre</th><th>Apellido</th><th>DNI</th><th>Email</th><th>Rol</th><th>Alta</th></tr></thead>
      <tbody>
      <?php foreach ($usuarios as $u): ?>
        <tr>
          <td><?= escapar($u['id']) ?></td>
          <td><?= escapar($u['nombre']) ?></td>
          <td><?= escapar($u['apellido']) ?></td>
          <td><?= escapar($u['dni']) ?></td>
          <td><?= escapar($u['email']) ?></td>
          <td><?= escapar($u['rol']) ?></td>
          <td><?= escapar(date('d/m/Y', strtotime($u['creado_en']))) ?></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php endif; ?>
</div>
<?php require_once __DIR__ . '/includes/pie.php'; ?>

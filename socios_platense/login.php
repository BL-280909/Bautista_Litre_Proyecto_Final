<?php
require_once __DIR__ . '/conexion.php';
require_once __DIR__ . '/includes/funciones.php';

if (hay_sesion()) { header('Location: ' . (es_admin() ? 'admin_usuarios.php' : 'panel.php')); exit; }

$errores = [];
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    $sql = 'SELECT id, nombre, email, password_hash, rol
            FROM usuarios
            WHERE email = :email AND archivado_en IS NULL
            LIMIT 1';
    $stmt = $conexion->prepare($sql);
    $stmt->execute(['email' => $email]);
    $usuario = $stmt->fetch();

    if ($usuario !== false && password_verify($password, $usuario['password_hash'])) {
        session_regenerate_id(true); // previene fijación de sesión
        $_SESSION['usuario_id'] = $usuario['id'];
        $_SESSION['usuario_nombre'] = $usuario['nombre'];
        $_SESSION['usuario_rol'] = $usuario['rol'];
        header('Location: ' . ($usuario['rol'] === 'admin' ? 'admin_usuarios.php' : 'panel.php'));
        exit;
    }
    // Mensaje genérico: no revela si falló el correo o la contraseña
    $errores[] = 'Correo o contraseña incorrectos.';
}

$titulo = 'Ingresar';
require_once __DIR__ . '/includes/cabecera.php';
?>
<div class="pagina">
  <h1>Ingresar</h1>
  <?php if (!empty($errores)): ?>
    <div class="error" role="alert"><?= escapar($errores[0]) ?></div>
  <?php endif; ?>
  <form class="formulario" action="login.php" method="POST">
    <label for="email">Correo electrónico</label>
    <input type="email" id="email" name="email" maxlength="254" value="<?= escapar($email) ?>" required>
    <label for="password">Contraseña</label>
    <input type="password" id="password" name="password" required>
    <button type="submit">Ingresar</button>
  </form>
  <p>¿No tenés cuenta? <a href="registro.php">Creala acá</a>.</p>
</div>
<?php require_once __DIR__ . '/includes/pie.php'; ?>

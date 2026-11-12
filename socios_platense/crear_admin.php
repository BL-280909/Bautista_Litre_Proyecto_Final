<?php
// HERRAMIENTA DE INSTALACIÓN DE USO ÚNICO.
// Después de crear el primer administrador, BORRÁ este archivo del servidor.
require_once __DIR__ . '/conexion.php';
require_once __DIR__ . '/includes/funciones.php';

// Si ya existe un admin, la herramienta se bloquea sola
$hayAdmin = (int)$conexion->query("SELECT COUNT(*) FROM usuarios WHERE rol = 'admin'")->fetchColumn() > 0;
if ($hayAdmin) {
    http_response_code(403);
    exit('Ya existe un administrador. Eliminá este archivo.');
}

$errores = [];
$mensaje = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = trim($_POST['nombre'] ?? '');
    $apellido = trim($_POST['apellido'] ?? '');
    $dni = trim($_POST['dni'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($nombre === '' || $apellido === '') { $errores[] = 'Nombre y apellido son obligatorios.'; }
    if (!preg_match('/^[0-9]{7,8}$/', $dni)) { $errores[] = 'El DNI debe tener 7 u 8 números.'; }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) { $errores[] = 'Ingresá un correo válido.'; }
    if (strlen($password) < 8) { $errores[] = 'La contraseña debe tener al menos 8 caracteres.'; }

    if (empty($errores)) {
        try {
            $stmt = $conexion->prepare('INSERT INTO usuarios (nombre, apellido, dni, email, password_hash, rol)
                VALUES (:nombre, :apellido, :dni, :email, :password_hash, :rol)');
            $stmt->execute([
                'nombre' => $nombre, 'apellido' => $apellido, 'dni' => $dni, 'email' => $email,
                'password_hash' => password_hash($password, PASSWORD_DEFAULT), 'rol' => 'admin',
            ]);
            $mensaje = 'Administrador creado. Ahora borrá crear_admin.php.';
        } catch (PDOException $e) {
            error_log($e->getMessage());
            $errores[] = 'No se pudo crear el administrador (¿DNI o correo repetido?).';
        }
    }
}

$titulo = 'Crear administrador';
require_once __DIR__ . '/includes/cabecera.php';
?>
<div class="pagina">
  <h1>Crear primer administrador</h1>
  <?php if ($mensaje !== ''): ?><p class="aviso"><?= escapar($mensaje) ?> <a href="login.php">Ingresar</a></p><?php endif; ?>
  <?php if (!empty($errores)): ?>
    <div class="error" role="alert"><ul><?php foreach ($errores as $e): ?><li><?= escapar($e) ?></li><?php endforeach; ?></ul></div>
  <?php endif; ?>
  <form class="formulario" action="crear_admin.php" method="POST">
    <label for="nombre">Nombre</label><input type="text" id="nombre" name="nombre" maxlength="80" required>
    <label for="apellido">Apellido</label><input type="text" id="apellido" name="apellido" maxlength="80" required>
    <label for="dni">DNI</label><input type="text" id="dni" name="dni" inputmode="numeric" pattern="[0-9]{7,8}" maxlength="8" required>
    <label for="email">Correo electrónico</label><input type="email" id="email" name="email" maxlength="254" required>
    <label for="password">Contraseña</label><input type="password" id="password" name="password" minlength="8" required>
    <button type="submit">Crear administrador</button>
  </form>
</div>
<?php require_once __DIR__ . '/includes/pie.php'; ?>

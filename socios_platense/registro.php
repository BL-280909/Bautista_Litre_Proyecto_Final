<?php
require_once __DIR__ . '/conexion.php';
require_once __DIR__ . '/includes/funciones.php';

$errores = [];
$mensaje = '';
$nombre = $apellido = $dni = $email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre   = trim($_POST['nombre'] ?? '');
    $apellido = trim($_POST['apellido'] ?? '');
    $dni      = trim($_POST['dni'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirmar = $_POST['confirmar'] ?? '';

    if ($nombre === '') { $errores[] = 'El nombre es obligatorio.'; }
    if ($apellido === '') { $errores[] = 'El apellido es obligatorio.'; }
    if (!preg_match('/^[0-9]{7,8}$/', $dni)) { $errores[] = 'El DNI debe tener 7 u 8 números.'; }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) { $errores[] = 'Ingresá un correo válido.'; }
    if (strlen($password) < 8) { $errores[] = 'La contraseña debe tener al menos 8 caracteres.'; }
    if ($password !== $confirmar) { $errores[] = 'Las contraseñas no coinciden.'; }

    if (empty($errores)) {
        try {
            $sql = 'INSERT INTO usuarios (nombre, apellido, dni, email, password_hash, rol)
                    VALUES (:nombre, :apellido, :dni, :email, :password_hash, :rol)';
            $stmt = $conexion->prepare($sql);
            $stmt->execute([
                'nombre' => $nombre,
                'apellido' => $apellido,
                'dni' => $dni,
                'email' => $email,
                'password_hash' => password_hash($password, PASSWORD_DEFAULT),
                'rol' => 'usuario', // fijo en el servidor: el registro público nunca crea admins
            ]);
            $mensaje = 'Cuenta creada correctamente. Ya podés ingresar.';
            $nombre = $apellido = $dni = $email = '';
        } catch (PDOException $e) {
            if ($e->getCode() === '23000') {
                $errores[] = 'Ya existe una cuenta con ese DNI o correo.';
            } else {
                error_log($e->getMessage());
                $errores[] = 'Ocurrió un error al crear la cuenta.';
            }
        }
    }
}

$titulo = 'Crear cuenta';
require_once __DIR__ . '/includes/cabecera.php';
?>
<div class="pagina">
  <h1>Crear cuenta</h1>
  <?php if ($mensaje !== ''): ?>
    <p class="aviso"><?= escapar($mensaje) ?> <a href="login.php">Ingresar</a></p>
  <?php endif; ?>
  <?php if (!empty($errores)): ?>
    <div class="error" role="alert"><strong>Revisá estos datos:</strong>
      <ul><?php foreach ($errores as $error): ?><li><?= escapar($error) ?></li><?php endforeach; ?></ul>
    </div>
  <?php endif; ?>
  <form class="formulario" action="registro.php" method="POST">
    <label for="nombre">Nombre</label>
    <input type="text" id="nombre" name="nombre" maxlength="80" value="<?= escapar($nombre) ?>" required>
    <label for="apellido">Apellido</label>
    <input type="text" id="apellido" name="apellido" maxlength="80" value="<?= escapar($apellido) ?>" required>
    <label for="dni">DNI</label>
    <input type="text" id="dni" name="dni" inputmode="numeric" pattern="[0-9]{7,8}" maxlength="8" value="<?= escapar($dni) ?>" required>
    <label for="email">Correo electrónico</label>
    <input type="email" id="email" name="email" maxlength="254" value="<?= escapar($email) ?>" required>
    <label for="password">Contraseña</label>
    <input type="password" id="password" name="password" minlength="8" required>
    <span class="ayuda">Mínimo 8 caracteres.</span>
    <label for="confirmar">Repetir contraseña</label>
    <input type="password" id="confirmar" name="confirmar" minlength="8" required>
    <button type="submit">Crear cuenta</button>
  </form>
</div>
<?php require_once __DIR__ . '/includes/pie.php'; ?>

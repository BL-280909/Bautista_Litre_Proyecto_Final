<?php
require_once __DIR__ . '/conexion.php';
require_once __DIR__ . '/includes/funciones.php';
requerir_login();

$stmt = $conexion->prepare('SELECT id, nombre, apellido, dni, email, password_hash
    FROM usuarios WHERE id = :id AND archivado_en IS NULL LIMIT 1');
$stmt->execute(['id' => $_SESSION['usuario_id']]);
$usuario = $stmt->fetch();
if ($usuario === false) {
    $_SESSION = [];
    session_destroy();
    header('Location: login.php');
    exit;
}

$stmt = $conexion->prepare('SELECT id, foto FROM socios WHERE dni = :dni LIMIT 1');
$stmt->execute(['dni' => $usuario['dni']]);
$socio = $stmt->fetch();

$errores = [];
$mensaje = '';
$nombre = $usuario['nombre'];
$apellido = $usuario['apellido'];
$email = $usuario['email'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $accion = $_POST['accion'] ?? '';
    $destino = null;
    $guardada = false;

    try {
        if ($accion === 'datos') {
            $nombre = trim($_POST['nombre'] ?? '');
            $apellido = trim($_POST['apellido'] ?? '');
            $email = trim($_POST['email'] ?? '');

            if ($nombre === '') { $errores[] = 'El nombre es obligatorio.'; }
            if ($apellido === '') { $errores[] = 'El apellido es obligatorio.'; }
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) { $errores[] = 'Ingresá un correo válido.'; }

            if (empty($errores)) {
                $conexion->beginTransaction();
                $stmt = $conexion->prepare('UPDATE usuarios SET nombre = :nombre, apellido = :apellido, email = :email WHERE id = :id');
                $stmt->execute(['nombre' => $nombre, 'apellido' => $apellido, 'email' => $email, 'id' => $usuario['id']]);
                if ($socio !== false) { // mantiene el padrón sincronizado
                    $stmt = $conexion->prepare('UPDATE socios SET nombre = :nombre, apellido = :apellido, email = :email WHERE id = :id');
                    $stmt->execute(['nombre' => $nombre, 'apellido' => $apellido, 'email' => $email, 'id' => $socio['id']]);
                }
                $conexion->commit();
                $_SESSION['usuario_nombre'] = $nombre;
                $mensaje = 'Tus datos se actualizaron correctamente.';
            }
        } elseif ($accion === 'password') {
            $actual = $_POST['actual'] ?? '';
            $nueva = $_POST['nueva'] ?? '';
            $confirmar = $_POST['confirmar'] ?? '';

            if (!password_verify($actual, $usuario['password_hash'])) { $errores[] = 'La contraseña actual no es correcta.'; }
            if (strlen($nueva) < 8) { $errores[] = 'La contraseña nueva debe tener al menos 8 caracteres.'; }
            if ($nueva !== $confirmar) { $errores[] = 'Las contraseñas nuevas no coinciden.'; }

            if (empty($errores)) {
                $stmt = $conexion->prepare('UPDATE usuarios SET password_hash = :hash WHERE id = :id');
                $stmt->execute(['hash' => password_hash($nueva, PASSWORD_DEFAULT), 'id' => $usuario['id']]);
                session_regenerate_id(true);
                $mensaje = 'Tu contraseña se cambió correctamente.';
            }
        } elseif ($accion === 'foto') {
            $f = $_FILES['foto'] ?? null;
            if ($socio === false) {
                $errores[] = 'Tu DNI todavía no figura en el padrón de socios.';
            } elseif ($f === null || $f['error'] === UPLOAD_ERR_NO_FILE) {
                $errores[] = 'Elegí una imagen.';
            } elseif ($f['error'] !== UPLOAD_ERR_OK) {
                $errores[] = 'No se pudo recibir la foto.';
            } elseif ($f['size'] > 2 * 1024 * 1024) {
                $errores[] = 'La foto pesa más de 2 MB.';
            } else {
                $finfo = new finfo(FILEINFO_MIME_TYPE);
                $tipoReal = $finfo->file($f['tmp_name']); // tipo REAL del archivo
                $permitidos = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
                if (!isset($permitidos[$tipoReal])) {
                    $errores[] = 'Formato no permitido. Usá JPG, PNG o WEBP.';
                } else {
                    $nombreArchivo = bin2hex(random_bytes(16)) . '.' . $permitidos[$tipoReal];
                    $destino = __DIR__ . '/uploads/socios/' . $nombreArchivo;
                    if (!move_uploaded_file($f['tmp_name'], $destino)) {
                        $errores[] = 'No se pudo guardar la foto en el servidor.';
                    } else {
                        $ruta = 'uploads/socios/' . $nombreArchivo;
                        $stmt = $conexion->prepare('UPDATE socios SET foto = :foto WHERE id = :id');
                        $stmt->execute(['foto' => $ruta, 'id' => $socio['id']]);
                        $guardada = true;
                        // borrar la foto anterior (solo si la ruta tiene el formato que generamos nosotros)
                        if (!empty($socio['foto']) && preg_match('#^uploads/socios/[a-f0-9]{32}\.(jpg|png|webp)$#', $socio['foto'])) {
                            $vieja = __DIR__ . '/' . $socio['foto'];
                            if (is_file($vieja)) { unlink($vieja); }
                        }
                        $socio['foto'] = $ruta;
                        $mensaje = 'Tu foto se actualizó correctamente.';
                    }
                }
            }
        }
    } catch (PDOException $e) {
        if ($conexion->inTransaction()) { $conexion->rollBack(); }
        if ($destino !== null && !$guardada && is_file($destino)) { unlink($destino); }
        if ($e->getCode() === '23000') {
            $errores[] = 'Ese correo ya está en uso por otra cuenta.';
        } else {
            error_log($e->getMessage());
            $errores[] = 'Ocurrió un error al guardar los cambios.';
        }
    }
}

$titulo = 'Mis datos';
require_once __DIR__ . '/includes/cabecera.php';
?>
<div class="pagina">
  <h1>Mis datos</h1>
  <?php if ($mensaje !== ''): ?><p class="aviso" role="status"><?= escapar($mensaje) ?></p><?php endif; ?>
  <?php if (!empty($errores)): ?>
    <div class="error" role="alert"><strong>Revisá estos datos:</strong>
      <ul><?php foreach ($errores as $e): ?><li><?= escapar($e) ?></li><?php endforeach; ?></ul>
    </div>
  <?php endif; ?>

  <h2>Datos personales</h2>
  <form class="formulario" action="perfil.php" method="POST">
    <input type="hidden" name="accion" value="datos">
    <label for="nombre">Nombre</label>
    <input type="text" id="nombre" name="nombre" maxlength="80" value="<?= escapar($nombre) ?>" required>
    <label for="apellido">Apellido</label>
    <input type="text" id="apellido" name="apellido" maxlength="80" value="<?= escapar($apellido) ?>" required>
    <label for="dni">DNI</label>
    <input type="text" id="dni" value="<?= escapar($usuario['dni']) ?>" readonly>
    <span class="ayuda">El DNI no se puede cambiar desde acá: lo modifica la administración.</span>
    <label for="email">Correo electrónico</label>
    <input type="email" id="email" name="email" maxlength="254" value="<?= escapar($email) ?>" required>
    <button type="submit">Guardar datos</button>
  </form>

  <div class="bloque">
    <h2>Foto de la credencial</h2>
    <?php if ($socio === false): ?>
      <p class="aviso">Cuando te asocies vas a poder subir tu foto. <a href="asociarme.php">Quiero asociarme</a>.</p>
    <?php else: ?>
      <?php if (!empty($socio['foto'])): ?>
        <img class="foto-grande" src="<?= escapar($socio['foto']) ?>" alt="Tu foto actual" style="margin-bottom:1rem">
      <?php endif; ?>
      <form class="formulario" action="perfil.php" method="POST" enctype="multipart/form-data">
        <input type="hidden" name="accion" value="foto">
        <label for="foto">Elegí una imagen</label>
        <input type="file" id="foto" name="foto" accept="image/jpeg,image/png,image/webp" required>
        <span class="ayuda">JPG, PNG o WEBP, hasta 2 MB.</span>
        <button type="submit">Subir foto</button>
      </form>
    <?php endif; ?>
  </div>

  <div class="bloque">
    <h2>Cambiar contraseña</h2>
    <form class="formulario" action="perfil.php" method="POST">
      <input type="hidden" name="accion" value="password">
      <label for="actual">Contraseña actual</label>
      <input type="password" id="actual" name="actual" required>
      <label for="nueva">Contraseña nueva</label>
      <input type="password" id="nueva" name="nueva" minlength="8" required>
      <span class="ayuda">Mínimo 8 caracteres.</span>
      <label for="confirmar">Repetir contraseña nueva</label>
      <input type="password" id="confirmar" name="confirmar" minlength="8" required>
      <button type="submit">Cambiar contraseña</button>
    </form>
  </div>
</div>
<?php require_once __DIR__ . '/includes/pie.php'; ?>

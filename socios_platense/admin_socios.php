<?php
require_once __DIR__ . '/conexion.php';
require_once __DIR__ . '/includes/funciones.php';
requerir_admin();

$categorias = ['Activo (18 años en adelante)', 'Cadete (13 a 18 años)', 'Vitalicio', 'Adherente', 'Menor (Recién nacido a 13 años)'];
$errores = [];
$mensaje = '';
$numero = $nombre = $apellido = $dni = $email = '';
$categoria = 'Activo';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $numero = trim($_POST['numero_socio'] ?? '');
    $nombre = trim($_POST['nombre'] ?? '');
    $apellido = trim($_POST['apellido'] ?? '');
    $dni = trim($_POST['dni'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $categoria = $_POST['categoria'] ?? '';

    if (!preg_match('/^[0-9]{1,9}$/', $numero) || (int)$numero < 1) { $errores[] = 'El número de socio debe ser un número positivo.'; }
    if ($nombre === '') { $errores[] = 'El nombre es obligatorio.'; }
    if ($apellido === '') { $errores[] = 'El apellido es obligatorio.'; }
    if (!preg_match('/^[0-9]{7,8}$/', $dni)) { $errores[] = 'El DNI debe tener 7 u 8 números.'; }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) { $errores[] = 'Ingresá un correo válido.'; }
    if (!in_array($categoria, $categorias, true)) { $errores[] = 'Categoría no válida.'; }

    // --- Foto (opcional): validación en el servidor ---
    $fotoTmp = null;
    $nombreArchivo = null;
    if (isset($_FILES['foto']) && $_FILES['foto']['error'] !== UPLOAD_ERR_NO_FILE) {
        $maximoBytes = 2 * 1024 * 1024; // 2 MB
        if ($_FILES['foto']['error'] !== UPLOAD_ERR_OK) {
            $errores[] = 'No se pudo recibir la foto.';
        } elseif ($_FILES['foto']['size'] > $maximoBytes) {
            $errores[] = 'La foto pesa más de 2 MB.';
        } else {
            $finfo = new finfo(FILEINFO_MIME_TYPE);
            $tipoReal = $finfo->file($_FILES['foto']['tmp_name']); // tipo REAL, no el del navegador
            $tiposPermitidos = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
            if (!isset($tiposPermitidos[$tipoReal])) {
                $errores[] = 'Formato no permitido. Usá JPG, PNG o WEBP.';
            } else {
                $fotoTmp = $_FILES['foto']['tmp_name'];
                $nombreArchivo = bin2hex(random_bytes(16)) . '.' . $tiposPermitidos[$tipoReal];
            }
        }
    }

    if (empty($errores)) {
        $rutaRelativa = null;
        $destino = null;
        if ($fotoTmp !== null) {
            $destino = __DIR__ . '/uploads/socios/' . $nombreArchivo;
            if (move_uploaded_file($fotoTmp, $destino)) {
                $rutaRelativa = 'uploads/socios/' . $nombreArchivo;
            } else {
                $errores[] = 'No se pudo guardar la foto en el servidor.';
            }
        }
    }

    if (empty($errores)) {
        try {
            $stmt = $conexion->prepare('INSERT INTO socios (numero_socio, nombre, apellido, dni, email, categoria, foto)
                VALUES (:numero_socio, :nombre, :apellido, :dni, :email, :categoria, :foto)');
            $stmt->execute([
                'numero_socio' => (int)$numero, 'nombre' => $nombre, 'apellido' => $apellido,
                'dni' => $dni, 'email' => $email, 'categoria' => $categoria, 'foto' => $rutaRelativa,
            ]);
            $mensaje = 'Socio registrado correctamente.';
            $numero = $nombre = $apellido = $dni = $email = '';
            $categoria = 'Activo';
        } catch (PDOException $e) {
            if ($destino !== null && is_file($destino)) { unlink($destino); } // no dejar fotos huérfanas
            if ($e->getCode() === '23000') {
                $errores[] = 'Ya existe un socio con ese número o DNI.';
            } else {
                error_log($e->getMessage());
                $errores[] = 'Ocurrió un error al guardar el socio.';
            }
        }
    }
}

$titulo = 'Cargar socio';
require_once __DIR__ . '/includes/cabecera.php';
?>
<div class="pagina">
  <h1>Cargar socio</h1>
  <?php if ($mensaje !== ''): ?><p class="aviso"><?= escapar($mensaje) ?> <a href="listado.php">Ver listado</a></p><?php endif; ?>
  <?php if (!empty($errores)): ?>
    <div class="error" role="alert"><strong>Revisá estos datos:</strong>
      <ul><?php foreach ($errores as $e): ?><li><?= escapar($e) ?></li><?php endforeach; ?></ul>
    </div>
  <?php endif; ?>
  <form class="formulario" action="admin_socios.php" method="POST" enctype="multipart/form-data">
    <label for="numero_socio">Número de socio</label>
    <input type="text" id="numero_socio" name="numero_socio" inputmode="numeric" pattern="[0-9]{1,9}" value="<?= escapar($numero) ?>" required>
    <label for="nombre">Nombre</label>
    <input type="text" id="nombre" name="nombre" maxlength="80" value="<?= escapar($nombre) ?>" required>
    <label for="apellido">Apellido</label>
    <input type="text" id="apellido" name="apellido" maxlength="80" value="<?= escapar($apellido) ?>" required>
    <label for="dni">DNI</label>
    <input type="text" id="dni" name="dni" inputmode="numeric" pattern="[0-9]{7,8}" maxlength="8" value="<?= escapar($dni) ?>" required>
    <label for="email">Correo electrónico</label>
    <input type="email" id="email" name="email" maxlength="254" value="<?= escapar($email) ?>" required>
    <label for="categoria">Categoría</label>
    <select id="categoria" name="categoria">
      <?php foreach ($categorias as $c): ?>
        <option value="<?= escapar($c) ?>" <?= $c === $categoria ? 'selected' : '' ?>><?= escapar($c) ?></option>
      <?php endforeach; ?>
    </select>
    <label for="foto">Foto del socio (opcional)</label>
    <input type="file" id="foto" name="foto" accept="image/jpeg,image/png,image/webp">
    <span class="ayuda">JPG, PNG o WEBP, hasta 2 MB.</span>
    <button type="submit">Guardar socio</button>
  </form>
</div>
<?php require_once __DIR__ . '/includes/pie.php'; ?>

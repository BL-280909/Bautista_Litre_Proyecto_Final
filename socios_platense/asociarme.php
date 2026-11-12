<?php
// Pantalla propia de asociación (no es el login).
// - Visitante sin cuenta: crea la cuenta Y queda como socio en un solo paso.
// - Usuario con sesión que aún no es socio: solo elige la categoría.
require_once __DIR__ . '/conexion.php';
require_once __DIR__ . '/includes/funciones.php';

$categorias = categorias_alta();
$errores = [];
$nombre = $apellido = $dni = $email = '';
$categoria = $categorias[0];
$usuario = false;

try {
    if (hay_sesion()) {
        $stmt = $conexion->prepare('SELECT id, nombre, apellido, dni, email FROM usuarios WHERE id = :id AND archivado_en IS NULL LIMIT 1');
        $stmt->execute(['id' => $_SESSION['usuario_id']]);
        $usuario = $stmt->fetch();
        if ($usuario === false) {
            $_SESSION = [];
            session_destroy();
            header('Location: login.php');
            exit;
        }
        $stmt = $conexion->prepare('SELECT id FROM socios WHERE dni = :dni LIMIT 1');
        $stmt->execute(['dni' => $usuario['dni']]);
        if ($stmt->fetch() !== false) { // ya está en el padrón
            header('Location: panel.php');
            exit;
        }
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $categoria = $_POST['categoria'] ?? '';
        if (!in_array($categoria, $categorias, true)) { $errores[] = 'Elegí una categoría válida.'; }

        if ($usuario === false) { // visitante: datos de la cuenta
            $nombre = trim($_POST['nombre'] ?? '');
            $apellido = trim($_POST['apellido'] ?? '');
            $dni = trim($_POST['dni'] ?? '');
            $email = trim($_POST['email'] ?? '');
            $password = $_POST['password'] ?? '';
            $confirmar = $_POST['confirmar'] ?? '';

            if ($nombre === '') { $errores[] = 'El nombre es obligatorio.'; }
            if ($apellido === '') { $errores[] = 'El apellido es obligatorio.'; }
            if (!preg_match('/^[0-9]{7,8}$/', $dni)) { $errores[] = 'El DNI debe tener 7 u 8 números.'; }
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) { $errores[] = 'Ingresá un correo válido.'; }
            if (strlen($password) < 8) { $errores[] = 'La contraseña debe tener al menos 8 caracteres.'; }
            if ($password !== $confirmar) { $errores[] = 'Las contraseñas no coinciden.'; }
        }

        if (empty($errores)) {
            $ok = false;
            $nuevoId = 0;
            if ($usuario === false) {
                $stmt = $conexion->prepare('SELECT COUNT(*) FROM socios WHERE dni = :dni');
                $stmt->execute(['dni' => $dni]);
                $yaEsSocio = (int)$stmt->fetchColumn() > 0; // lo cargó antes el administrador
                $hash = password_hash($password, PASSWORD_DEFAULT);
            }

            // Hasta 3 intentos por si dos personas se asocian a la vez y chocan en el número de socio
            for ($i = 0; $i < 3 && !$ok; $i++) {
                try {
                    $conexion->beginTransaction();
                    if ($usuario === false) {
                        $stmt = $conexion->prepare('INSERT INTO usuarios (nombre, apellido, dni, email, password_hash, rol)
                            VALUES (:nombre, :apellido, :dni, :email, :hash, :rol)');
                        $stmt->execute(['nombre' => $nombre, 'apellido' => $apellido, 'dni' => $dni,
                            'email' => $email, 'hash' => $hash, 'rol' => 'usuario']); // rol fijo en el servidor
                        $nuevoId = (int)$conexion->lastInsertId();
                        $datos = ['nombre' => $nombre, 'apellido' => $apellido, 'dni' => $dni, 'email' => $email];
                        $cargarSocio = !$yaEsSocio;
                    } else {
                        $datos = ['nombre' => $usuario['nombre'], 'apellido' => $usuario['apellido'],
                            'dni' => $usuario['dni'], 'email' => $usuario['email']];
                        $cargarSocio = true;
                    }
                    if ($cargarSocio) {
                        $numero = (int)$conexion->query('SELECT COALESCE(MAX(numero_socio), 0) + 1 FROM socios')->fetchColumn();
                        $stmt = $conexion->prepare('INSERT INTO socios (numero_socio, nombre, apellido, dni, email, categoria)
                            VALUES (:numero, :nombre, :apellido, :dni, :email, :categoria)');
                        $stmt->execute($datos + ['numero' => $numero, 'categoria' => $categoria]);
                    }
                    $conexion->commit();
                    $ok = true;
                } catch (PDOException $e) {
                    if ($conexion->inTransaction()) { $conexion->rollBack(); }
                    if ($e->getCode() !== '23000') { throw $e; }
                    if ($usuario === false) {
                        $stmt = $conexion->prepare('SELECT COUNT(*) FROM usuarios WHERE dni = :dni OR email = :email');
                        $stmt->execute(['dni' => $dni, 'email' => $email]);
                        if ((int)$stmt->fetchColumn() > 0) {
                            $errores[] = 'Ya existe una cuenta con ese DNI o correo. Si es tuya, ingresá y asociate desde ahí.';
                            break;
                        }
                    }
                    // si no fue un duplicado de cuenta, fue un choque de número: se reintenta
                }
            }

            if ($ok) {
                if ($usuario === false) { // inicia la sesión de la cuenta recién creada
                    session_regenerate_id(true);
                    $_SESSION['usuario_id'] = $nuevoId;
                    $_SESSION['usuario_nombre'] = $nombre;
                    $_SESSION['usuario_rol'] = 'usuario';
                }
                header('Location: panel.php?bienvenida=1');
                exit;
            }
            if (empty($errores)) { $errores[] = 'No se pudo completar el alta. Probá de nuevo.'; }
        }
    }
} catch (PDOException $e) {
    error_log($e->getMessage());
    http_response_code(500);
    exit('No se pudo procesar el alta.');
}

$titulo = 'Quiero asociarme';
require_once __DIR__ . '/includes/cabecera.php';
?>
<div class="pagina">
  <h1>Quiero asociarme</h1>
  <?php if (!empty($errores)): ?>
    <div class="error" role="alert"><strong>Revisá estos datos:</strong>
      <ul><?php foreach ($errores as $e): ?><li><?= escapar($e) ?></li><?php endforeach; ?></ul>
    </div>
  <?php endif; ?>

  <?php if ($usuario !== false): ?>
    <p>Te vas a asociar con los datos de tu cuenta:
       <strong><?= escapar($usuario['nombre'] . ' ' . $usuario['apellido']) ?></strong> · DNI <?= escapar($usuario['dni']) ?>.
       Quedás en el padrón y recibís tu número de socio al instante.</p>
  <?php else: ?>
    <p>Completá el formulario: <strong>creás tu cuenta y te asociás en un solo paso</strong>, con tu número de socio al instante.
       ¿Ya tenés cuenta? <a href="login.php">Ingresá</a> y asociate desde ahí.</p>
  <?php endif; ?>

  <form class="formulario" action="asociarme.php" method="POST">
    <?php if ($usuario === false): ?>
      <h2>Creá tu cuenta</h2>
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
    <?php endif; ?>
    <h2 style="margin-top:1.2rem">Elegí tu categoría</h2>
    <label for="categoria">Categoría</label>
    <select id="categoria" name="categoria">
      <?php foreach ($categorias as $c): ?>
        <option value="<?= escapar($c) ?>" <?= $c === $categoria ? 'selected' : '' ?>><?= escapar($c) ?></option>
      <?php endforeach; ?>
    </select>
    <button type="submit">Asociarme</button>
  </form>
</div>
<?php require_once __DIR__ . '/includes/pie.php'; ?>

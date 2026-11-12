<?php
require_once __DIR__ . '/funciones.php';
$titulo = $titulo ?? 'Página de socios del Club Atlético Platense';
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= escapar($titulo) ?> - Club Atlético Platense</title>
<link rel="stylesheet" href="styles.css?v=<?= filemtime(__DIR__ . '/../styles.css') ?>">
</head>
<body>
<header class="barra">
  <div class="contenedor barra-fila">
    <a class="marca" href="index.php">
      <img src="img/escudo.png" alt="Escudo del Club Atlético Platense" height="52">
      <span>Página de socios del Club Atlético Platense</span>
    </a>
    <nav aria-label="Principal">
      <a href="index.php">Inicio</a>
      <a href="listado.php">Socios</a>
      <?php if (hay_sesion()): ?>
        <a href="panel.php">Mi panel</a>
        <a href="perfil.php">Mis datos</a>
        <?php if (es_admin()): ?>
          <a href="admin_socios.php">Cargar socio</a>
          <a href="admin_cuotas.php">Cuotas</a>
          <a href="admin_usuarios.php">Usuarios</a>
        <?php endif; ?>
        <a href="cerrar_sesion.php">Salir</a>
      <?php else: ?>
        <a href="login.php">Ingresar</a>
        <a href="registro.php">Crear cuenta</a>
      <?php endif; ?>
    </nav>
  </div>
</header>
<main class="contenedor">

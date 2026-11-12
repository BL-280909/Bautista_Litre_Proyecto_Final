<?php
if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params(['httponly' => true, 'samesite' => 'Lax']);
    session_start();
}

// Escapa cualquier valor antes de mostrarlo (evita XSS)
function escapar($valor): string
{
    return htmlspecialchars((string)($valor ?? ''), ENT_QUOTES, 'UTF-8');
}

function hay_sesion(): bool
{
    return isset($_SESSION['usuario_id']);
}

function es_admin(): bool
{
    return hay_sesion() && ($_SESSION['usuario_rol'] ?? '') === 'admin';
}

function requerir_login(): void
{
    if (!hay_sesion()) {
        header('Location: login.php');
        exit;
    }
}

// Se llama ANTES de enviar cualquier HTML
function requerir_admin(): void
{
    if (!es_admin()) {
        http_response_code(403);
        exit('Acceso denegado.');
    }
}

// Categorías que un usuario puede elegir al asociarse
function categorias_alta(): array
{
    return ['Activo', 'Cadete', 'Adherente'];
}

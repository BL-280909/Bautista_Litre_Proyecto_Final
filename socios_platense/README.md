# Socios del Club Atlético Platense

Proyecto final de Programación (PHP + PDO + MySQL). Colores: marrón y blanco del escudo.

## Puesta en marcha (XAMPP, Windows)
1. Copiá la carpeta `socios_platense` a `C:\xampp\htdocs\`.
2. Iniciá Apache y MySQL desde XAMPP.
3. En http://localhost/phpmyadmin → pestaña SQL → pegá y ejecutá `sql/base_datos.sql`.
4. Abrí http://localhost/socios_platense/crear_admin.php y creá el primer administrador.
5. **Borrá `crear_admin.php`** (se bloquea solo cuando ya hay un admin, pero igual borralo).
6. Ejecutá también `sql/cuotas.sql` en la pestaña SQL de phpMyAdmin (tabla de cuotas).
7. Ingresá en http://localhost/socios_platense/login.php.

## Archivos
- `conexion.php`: conexión PDO.
- `registro.php` / `login.php` / `cerrar_sesion.php`: cuentas y sesiones.
- `admin_usuarios.php`: crear y listar usuarios (solo admin).
- `admin_socios.php`: cargar socios con foto (solo admin).
- `admin_cuotas.php`: generar la cuota del mes y marcar cuotas como pagadas (solo admin).
- `panel.php`: cuotas pendientes e historial de pagos del socio (se vincula por DNI con el padrón); el administrador ve además sus accesos.
- `perfil.php`: el usuario modifica nombre, apellido, correo, foto y contraseña (el DNI lo cambia la administración).
- `asociarme.php`: pantalla propia para asociarse. Un visitante crea su cuenta y queda como socio en un solo paso; un usuario con sesión solo elige la categoría.
- `listado.php`: SELECT + foreach + tabla HTML con los socios.
- `uploads/.htaccess`: bloquea la ejecución de scripts en la carpeta de fotos.

## Pruebas sugeridas
Registro válido, DNI/correo duplicado, contraseña incorrecta, usuario común entrando a
`admin_usuarios.php` por URL (debe dar 403), subir un `.php` renombrado como `.jpg` (debe rechazarse).

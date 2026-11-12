CREATE DATABASE platense_socios
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;
USE platense_socios;

-- Cuentas que pueden iniciar sesión (registro público o administrativo)
CREATE TABLE usuarios (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  nombre VARCHAR(80) NOT NULL,
  apellido VARCHAR(80) NOT NULL,
  dni VARCHAR(20) NOT NULL UNIQUE,
  email VARCHAR(254) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  rol VARCHAR(20) NOT NULL DEFAULT 'usuario',
  creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  archivado_en TIMESTAMP NULL DEFAULT NULL
);

-- Padrón de socios del club (con foto: se guarda la ruta, no el archivo)
CREATE TABLE socios (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  numero_socio INT UNSIGNED NOT NULL UNIQUE,
  nombre VARCHAR(80) NOT NULL,
  apellido VARCHAR(80) NOT NULL,
  dni VARCHAR(20) NOT NULL UNIQUE,
  email VARCHAR(254) NOT NULL,
  categoria VARCHAR(20) NOT NULL DEFAULT 'Activo',
  foto VARCHAR(255) DEFAULT NULL,
  fecha_registro TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

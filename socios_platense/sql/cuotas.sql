USE platense_socios;

-- Una fila por socio y por mes. estado: 'pendiente' o 'pagada'
CREATE TABLE cuotas (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  socio_id INT UNSIGNED NOT NULL,
  periodo CHAR(7) NOT NULL,              -- formato AAAA-MM, ej: 2026-11
  monto DECIMAL(10,2) NOT NULL,
  estado VARCHAR(10) NOT NULL DEFAULT 'pendiente',
  fecha_pago DATE DEFAULT NULL,
  UNIQUE KEY socio_periodo (socio_id, periodo),
  FOREIGN KEY (socio_id) REFERENCES socios(id) ON DELETE CASCADE
);

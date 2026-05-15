-- ============================================
-- Base de datos: DenTera - Gestión de Citas
-- Instalación: importar el archivo completo (crea BD, tablas y datos demo).
-- Actualización: al final hay bloques idempotentes que añaden columnas
-- faltantes en bases ya existentes (notas_clinicas, orden_agenda, índice).
-- ============================================

CREATE DATABASE IF NOT EXISTS dentista CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

USE dentista;

CREATE TABLE IF NOT EXISTS usuarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    rol ENUM('admin','auxiliar') DEFAULT 'auxiliar',
    activo TINYINT(1) DEFAULT 1,
    creado TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS auditoria (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT NULL,
    accion VARCHAR(50) NOT NULL,
    tabla VARCHAR(50) NOT NULL,
    registro_id INT NULL,
    descripcion TEXT,
    creado TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_usuario (usuario_id),
    INDEX idx_accion (accion)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS doctores (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    especialidad VARCHAR(100) DEFAULT '',
    tipo ENUM('doctor','higienista') DEFAULT 'doctor',
    telefono VARCHAR(20) DEFAULT '',
    email VARCHAR(120) DEFAULT '',
    activo TINYINT(1) DEFAULT 1,
    usuario_id INT NULL,
    creado TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_doc_usuario (usuario_id),
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS tratamientos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL UNIQUE,
    descripcion VARCHAR(255) DEFAULT '',
    duracion INT DEFAULT 30,
    precio DECIMAL(8,2) DEFAULT 0.00,
    activo TINYINT(1) DEFAULT 1,
    usuario_id INT NULL,
    creado TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_trat_usuario (usuario_id),
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS config (
    id INT AUTO_INCREMENT PRIMARY KEY,
    clave VARCHAR(50) NOT NULL UNIQUE,
    valor TEXT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS pacientes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(120) NOT NULL,
    dni VARCHAR(20) DEFAULT '',
    telefono VARCHAR(20) DEFAULT '',
    email VARCHAR(120) DEFAULT '',
    notas_clinicas TEXT NULL COMMENT 'Alergias, antecedentes, observaciones clínicas',
    creado TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    actualizado TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_nombre (nombre),
    INDEX idx_dni (dni),
    INDEX idx_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS citas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    paciente_id INT NULL,
    paciente VARCHAR(120) NOT NULL,
    dni VARCHAR(20) DEFAULT '',
    telefono VARCHAR(20) DEFAULT '',
    email VARCHAR(120) DEFAULT '',
    fecha DATE NOT NULL,
    hora TIME NOT NULL,
    orden_agenda INT NULL DEFAULT NULL COMMENT 'Orden manual en listado (misma fecha)',
    dentista VARCHAR(100) DEFAULT '',
    doctor_id INT NULL,
    motivo VARCHAR(255) DEFAULT '',
    tratamiento_id INT NULL,
    estado ENUM('pendiente', 'confirmada', 'cancelada', 'completada') DEFAULT 'pendiente',
    forma_pago VARCHAR(30) DEFAULT '',
    usuario_id INT NULL,
    notas TEXT,
    creado TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    actualizado TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_fecha (fecha),
    INDEX idx_cita_fecha_orden (fecha, orden_agenda),
    INDEX idx_estado (estado),
    INDEX idx_paciente (paciente),
    INDEX idx_cita_paciente (paciente_id),
    INDEX idx_doctor (doctor_id),
    INDEX idx_tratamiento (tratamiento_id),
    INDEX idx_cita_usuario (usuario_id),
    FOREIGN KEY (paciente_id) REFERENCES pacientes(id) ON DELETE SET NULL,
    FOREIGN KEY (doctor_id) REFERENCES doctores(id) ON DELETE SET NULL,
    FOREIGN KEY (tratamiento_id) REFERENCES tratamientos(id) ON DELETE SET NULL,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Usuarios por defecto (contraseñas en texto plano: admin123 / auxiliar123)
INSERT INTO usuarios (nombre, password, rol) VALUES
    ('admin', '$2y$10$h.ggT4rykz/Ch4Emf4l4de9I6kBZkT7n8dKfBcOKava/U/UDE0.pe', 'admin'),
    ('auxiliar', '$2y$10$0664psrwG7XFTjBjIcau..YRiI55WN3jztda7.G5TkiKg7Rqp7Mey', 'auxiliar');

INSERT INTO doctores (nombre, especialidad, tipo) VALUES
    ('Dr. General', 'Odontología General', 'doctor'),
    ('Dra. Martínez', 'Ortodoncia', 'doctor'),
    ('Dr. López', 'Endodoncia', 'doctor'),
    ('Dra. Sánchez', 'Periodoncia', 'doctor'),
    ('Hig. Fernández', 'Higiene Dental', 'higienista');

INSERT INTO tratamientos (nombre, descripcion, duracion, precio) VALUES
    ('Revisión general', 'Examen completo de boca y dientes', 20, 30.00),
    ('Limpieza dental', 'Limpieza profesional con ultrasonidos', 45, 50.00),
    ('Empaste', 'Restauración de caries con composite', 30, 45.00),
    ('Endodoncia', 'Tratamiento de conductos radiculares', 60, 150.00),
    ('Extracción', 'Extracción de pieza dental', 30, 60.00),
    ('Ortodoncia', 'Revisión y ajuste de brackets', 30, 80.00),
    ('Blanqueamiento', 'Blanqueamiento dental profesional', 45, 200.00),
    ('Corona', 'Colocación de corona dental', 60, 300.00),
    ('Implante', 'Colocación de implante dental', 90, 800.00),
    ('Periodoncia', 'Tratamiento de encías', 45, 100.00);

INSERT INTO config (clave, valor) VALUES
    ('clinica_nombre', 'Clínica Dental DenTera'),
    ('clinica_cif', 'B12345678'),
    ('clinica_direccion', 'Calle Principal 1, 28001 Madrid'),
    ('clinica_telefono', '912 345 678'),
    ('clinica_email', 'info@dentara.es'),
    ('clinica_web', 'www.dentara.es');

-- Pacientes y citas de ejemplo (mayo 2026; ajusta fechas si reimportas en otro mes)
INSERT INTO pacientes (nombre, dni, telefono, email) VALUES
    ('Ana García Ruiz', '12345678A', '612111222', 'ana.garcia@email.com'),
    ('Luis Morales Soto', '87654321B', '623333444', 'luis.morales@email.com'),
    ('Carmen Vidal Núñez', '11223344C', '634555666', 'carmen.vidal@email.com'),
    ('Javier Prado León', '55667788D', '645777888', 'javier.prado@email.com'),
    ('Marta Iglesias Rey', '99887766E', '656999000', 'marta.iglesias@email.com'),
    ('Roberto Sáenz Gil', '44332211F', '611222333', 'roberto.saenz@email.com');

INSERT INTO citas (paciente_id, paciente, dni, telefono, email, fecha, hora, doctor_id, motivo, tratamiento_id, estado, forma_pago, notas) VALUES
    (1, 'Ana García Ruiz', '12345678A', '612111222', 'ana.garcia@email.com', '2026-05-12', '09:00:00', 1, 'Revisión anual', 1, 'confirmada', 'tarjeta', ''),
    (2, 'Luis Morales Soto', '87654321B', '623333444', 'luis.morales@email.com', '2026-05-12', '10:30:00', 1, 'Dolor molar superior derecho', 3, 'pendiente', '', ''),
    (3, 'Carmen Vidal Núñez', '11223344C', '634555666', 'carmen.vidal@email.com', '2026-05-12', '11:30:00', 2, 'Ajuste de brackets', 6, 'confirmada', '', ''),
    (4, 'Javier Prado León', '55667788D', '645777888', 'javier.prado@email.com', '2026-05-12', '16:00:00', 3, 'Conducto pieza 16', 4, 'pendiente', '', ''),
    (5, 'Marta Iglesias Rey', '99887766E', '656999000', 'marta.iglesias@email.com', '2026-05-12', '17:30:00', 5, 'Limpieza semestral', 2, 'completada', 'bizum', ''),
    (6, 'Roberto Sáenz Gil', '44332211F', '611222333', 'roberto.saenz@email.com', '2026-05-13', '09:15:00', 1, 'Primera visita / valoración', 1, 'pendiente', '', ''),
    (1, 'Ana García Ruiz', '12345678A', '612111222', 'ana.garcia@email.com', '2026-05-14', '12:00:00', 2, 'Control ortodoncia', 6, 'confirmada', '', ''),
    (3, 'Carmen Vidal Núñez', '11223344C', '634555666', 'carmen.vidal@email.com', '2026-05-19', '10:00:00', 4, 'Encía sensible', 10, 'pendiente', '', ''),
    (4, 'Javier Prado León', '55667788D', '645777888', 'javier.prado@email.com', '2026-05-19', '11:00:00', 3, 'Segunda sesión endodoncia', 4, 'confirmada', '', ''),
    (2, 'Luis Morales Soto', '87654321B', '623333444', 'luis.morales@email.com', '2026-05-20', '08:30:00', 1, 'Empaste 24', 3, 'completada', 'efectivo', ''),
    (6, 'Roberto Sáenz Gil', '44332211F', '611222333', 'roberto.saenz@email.com', '2026-05-22', '15:00:00', 5, 'Blanqueamiento (sesión 1)', 7, 'pendiente', '', ''),
    (5, 'Marta Iglesias Rey', '99887766E', '656999000', 'marta.iglesias@email.com', '2026-06-02', '09:00:00', 1, 'Revisión post-tratamiento', 1, 'pendiente', '', '');

-- =============================================================================
-- Actualización idempotente (bases ya existentes sin estas columnas)
-- En instalaciones nuevas las tablas anteriores ya incluyen la columna y
-- estas sentencias no hacen cambios. Si `pacientes` se creó con un schema
-- antiguo, aquí se añade `notas_clinicas` sin fallar al repetir la importación.
-- =============================================================================

SET @__dn = DATABASE();
SET @__sql_notas = (
    SELECT IF(
        (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
         WHERE TABLE_SCHEMA = @__dn AND TABLE_NAME = 'pacientes' AND COLUMN_NAME = 'notas_clinicas') > 0,
        'SELECT 1',
        'ALTER TABLE pacientes ADD COLUMN notas_clinicas TEXT NULL COMMENT ''Alergias, antecedentes, observaciones clínicas'' AFTER email'
    )
);
PREPARE __stmt_notas FROM @__sql_notas;
EXECUTE __stmt_notas;
DEALLOCATE PREPARE __stmt_notas;

SET @__sql_orden = (
    SELECT IF(
        (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
         WHERE TABLE_SCHEMA = @__dn AND TABLE_NAME = 'citas' AND COLUMN_NAME = 'orden_agenda') > 0,
        'SELECT 1',
        'ALTER TABLE citas ADD COLUMN orden_agenda INT NULL DEFAULT NULL COMMENT ''Orden manual en listado (misma fecha)'' AFTER hora'
    )
);
PREPARE __stmt_orden FROM @__sql_orden;
EXECUTE __stmt_orden;
DEALLOCATE PREPARE __stmt_orden;

SET @__idx_orden = (
    SELECT IF(
        (SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS
         WHERE TABLE_SCHEMA = @__dn AND TABLE_NAME = 'citas' AND INDEX_NAME = 'idx_cita_fecha_orden') > 0,
        'SELECT 1',
        'CREATE INDEX idx_cita_fecha_orden ON citas (fecha, orden_agenda)'
    )
);
PREPARE __stmt_idx FROM @__idx_orden;
EXECUTE __stmt_idx;
DEALLOCATE PREPARE __stmt_idx;

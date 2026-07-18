-- ============================================================
-- Schema base — Plataforma de Proyectos de Vinculación
-- ============================================================

-- Roles
CREATE TABLE IF NOT EXISTS roles (
  id_rol INT AUTO_INCREMENT PRIMARY KEY,
  nombre VARCHAR(50) NOT NULL UNIQUE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO roles (nombre) VALUES ('Coordinador'), ('Tutor'), ('Estudiante');

-- Usuarios
CREATE TABLE IF NOT EXISTS usuarios (
  id_usuario INT AUTO_INCREMENT PRIMARY KEY,
  nombre VARCHAR(100) NOT NULL,
  apellido VARCHAR(100) NOT NULL,
  correo VARCHAR(150) NOT NULL UNIQUE,
  password VARCHAR(255) NOT NULL,
  rol_id INT NOT NULL,
  estado ENUM('activo','inactivo') DEFAULT 'activo',
  FOREIGN KEY (rol_id) REFERENCES roles(id_rol)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Contraseña por defecto: Coord123*, Tutor123*, Est12345*
INSERT INTO usuarios (nombre, apellido, correo, password, rol_id, estado) VALUES
('Adrian', 'Gavilanes', 'coordinador@ug.edu.ec', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, 'activo'),
('Carlos', 'Mendoza', 'tutor@ug.edu.ec', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 2, 'activo'),
('Maria', 'Lopez', 'estudiante@ug.edu.ec', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 3, 'activo');

-- Facultades
CREATE TABLE IF NOT EXISTS facultades (
  id_facultad INT AUTO_INCREMENT PRIMARY KEY,
  nombre VARCHAR(150) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO facultades (nombre) VALUES ('Facultad de Ingeniería'), ('Facultad de Ciencias');

-- Carreras
CREATE TABLE IF NOT EXISTS carreras (
  id_carrera INT AUTO_INCREMENT PRIMARY KEY,
  nombre VARCHAR(150) NOT NULL,
  facultad_id INT NOT NULL,
  FOREIGN KEY (facultad_id) REFERENCES facultades(id_facultad)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO carreras (nombre, facultad_id) VALUES
('Ingeniería en Sistemas', 1),
('Ingeniería Civil', 1),
('Matemáticas', 2);

-- Proyectos
CREATE TABLE IF NOT EXISTS proyectos (
  id_proyecto INT AUTO_INCREMENT PRIMARY KEY,
  nombre VARCHAR(200) NOT NULL,
  descripcion TEXT,
  tutor_id INT NOT NULL,
  facultad_id INT NOT NULL,
  carrera_id INT NOT NULL,
  cupos_max INT DEFAULT 60,
  cupos_usados INT DEFAULT 0,
  estado ENUM('activo','inactivo','finalizado') DEFAULT 'activo',
  fecha_inicio DATE,
  fecha_fin DATE,
  FOREIGN KEY (tutor_id) REFERENCES usuarios(id_usuario),
  FOREIGN KEY (facultad_id) REFERENCES facultades(id_facultad),
  FOREIGN KEY (carrera_id) REFERENCES carreras(id_carrera)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Solicitudes
CREATE TABLE IF NOT EXISTS solicitudes (
  id_solicitud INT AUTO_INCREMENT PRIMARY KEY,
  estudiante_id INT NOT NULL,
  proyecto_id INT NOT NULL,
  estado ENUM('pendiente','aceptada','denegada') DEFAULT 'pendiente',
  fecha_solicitud DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (estudiante_id) REFERENCES usuarios(id_usuario),
  FOREIGN KEY (proyecto_id) REFERENCES proyectos(id_proyecto)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- NUEVA TABLA: Actividades (las que el tutor crea)
-- ============================================================
CREATE TABLE IF NOT EXISTS actividades (
  id_actividad INT AUTO_INCREMENT PRIMARY KEY,
  proyecto_id INT NOT NULL,
  tutor_id INT NOT NULL,
  fecha DATE NOT NULL,
  descripcion TEXT NOT NULL,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (proyecto_id) REFERENCES proyectos(id_proyecto),
  FOREIGN KEY (tutor_id) REFERENCES usuarios(id_usuario)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Horas de vinculación
CREATE TABLE IF NOT EXISTS horas_vinculacion (
  id_hora INT AUTO_INCREMENT PRIMARY KEY,
  estudiante_id INT NOT NULL,
  proyecto_id INT NOT NULL,
  tutor_id INT NOT NULL,
  actividad_id INT,
  fecha_actividad DATE NOT NULL,
  descripcion TEXT,
  horas DECIMAL(4,1) NOT NULL,
  estado ENUM('pendiente','aprobada','rechazada') DEFAULT 'pendiente',
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (estudiante_id) REFERENCES usuarios(id_usuario),
  FOREIGN KEY (proyecto_id) REFERENCES proyectos(id_proyecto),
  FOREIGN KEY (tutor_id) REFERENCES usuarios(id_usuario),
  FOREIGN KEY (actividad_id) REFERENCES actividades(id_actividad)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

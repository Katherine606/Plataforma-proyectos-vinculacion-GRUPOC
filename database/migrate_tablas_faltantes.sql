-- Solo las tablas que faltan (NO toca usuarios, roles, etc.)

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

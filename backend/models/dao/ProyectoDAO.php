<?php

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../dto/ProyectoDTO.php';

class ProyectoDAO
{
    private PDO $db;

    private const SELECT_CON_JOINS = "
        SELECT p.id_proyecto as id, p.nombre, p.descripcion,
               CONCAT(t.nombre, ' ', t.apellido) as tutor,
               f.nombre as facultad,
               c.nombre as carrera,
               p.cupos_max, p.cupos_usados, p.estado,
               p.fecha_inicio, p.fecha_fin
        FROM proyectos p
        LEFT JOIN usuarios t ON p.tutor_id = t.id_usuario
        LEFT JOIN facultades f ON p.facultad_id = f.id_facultad
        LEFT JOIN carreras c ON p.carrera_id = c.id_carrera
    ";

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    public function listar(): array
    {
        $stmt = $this->db->query(self::SELECT_CON_JOINS . " ORDER BY p.id_proyecto DESC");
        return $stmt->fetchAll();
    }

    public function listarPorTutor(int $tutorId): array
    {
        $stmt = $this->db->prepare(self::SELECT_CON_JOINS . " WHERE p.tutor_id = :tid ORDER BY p.nombre");
        $stmt->execute([':tid' => $tutorId]);
        return $stmt->fetchAll();
    }

    public function obtenerPorId(int $id): ?array
    {
        $stmt = $this->db->prepare(self::SELECT_CON_JOINS . " WHERE p.id_proyecto = :id");
        $stmt->execute([':id' => $id]);
        return $stmt->fetch() ?: null;
    }

    public function insertar(array $data): int
    {
        $stmt = $this->db->prepare("
            INSERT INTO proyectos (nombre, descripcion, tutor_id, facultad_id, carrera_id, cupos_max, cupos_usados, estado, fecha_inicio, fecha_fin)
            VALUES (:nombre, :descripcion, :tutor_id, :facultad_id, :carrera_id, :cupos_max, 0, 'activo', :fecha_inicio, :fecha_fin)
        ");
        $stmt->execute([
            ':nombre'       => $data['nombre'],
            ':descripcion'  => $data['descripcion'],
            ':tutor_id'     => $data['tutor_id'],
            ':facultad_id'  => $data['facultad_id'],
            ':carrera_id'   => $data['carrera_id'],
            ':cupos_max'    => min((int) $data['cupos_max'], 60),
            ':fecha_inicio' => !empty($data['fecha_inicio']) ? $data['fecha_inicio'] : null,
            ':fecha_fin'    => !empty($data['fecha_fin']) ? $data['fecha_fin'] : null,
        ]);
        return (int) $this->db->lastInsertId();
    }

    public function actualizar(int $id, array $data): bool
    {
        $camposPermitidos = ['nombre', 'descripcion', 'tutor_id', 'facultad_id', 'carrera_id', 'cupos_max', 'estado', 'fecha_inicio', 'fecha_fin'];
        $sets   = [];
        $params = [':id' => $id];

        foreach ($camposPermitidos as $campo) {
            if (array_key_exists($campo, $data)) {
                $sets[]            = "$campo = :$campo";
                $params[":$campo"] = $data[$campo];
            }
        }

        if (empty($sets)) {
            return false;
        }

        $sql  = "UPDATE proyectos SET " . implode(', ', $sets) . " WHERE id_proyecto = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return true;
    }

    public function eliminar(int $id): bool
    {
        $stmt = $this->db->prepare("DELETE FROM proyectos WHERE id_proyecto = :id");
        $stmt->execute([':id' => $id]);
        return $stmt->rowCount() > 0;
    }

    public function verificarTutor(int $tutorId): bool
    {
        $stmt = $this->db->prepare("SELECT id_usuario FROM usuarios WHERE id_usuario = :id AND rol_id = 2");
        $stmt->execute([':id' => $tutorId]);
        return (bool) $stmt->fetch();
    }

    public function obtenerTutorId(int $proyectoId): ?int
    {
        $stmt = $this->db->prepare("SELECT tutor_id FROM proyectos WHERE id_proyecto = :pid");
        $stmt->execute([':pid' => $proyectoId]);
        $row = $stmt->fetch();
        return $row ? (int) $row['tutor_id'] : null;
    }

    public function incrementarCupos(int $proyectoId): bool
    {
        $stmt = $this->db->prepare("
            UPDATE proyectos SET cupos_usados = cupos_usados + 1
            WHERE id_proyecto = :pid AND cupos_usados < cupos_max
        ");
        $stmt->execute([':pid' => $proyectoId]);
        return $stmt->rowCount() > 0;
    }
}

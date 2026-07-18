<?php

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../dto/ActividadDTO.php';

class ActividadDAO
{
    private PDO $db;

    private const SELECT_CON_JOINS = "
        SELECT a.id_actividad as id,
               a.proyecto_id as id_proyecto,
               p.nombre as nombre_proyecto,
               a.tutor_id as id_tutor,
               CONCAT(t.nombre, ' ', t.apellido) as tutor,
               a.fecha,
               a.descripcion,
               a.created_at
        FROM actividades a
        JOIN proyectos p ON a.proyecto_id = p.id_proyecto
        JOIN usuarios t ON a.tutor_id = t.id_usuario
    ";

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    public function listarPorTutor(int $tutorId): array
    {
        $stmt = $this->db->prepare(self::SELECT_CON_JOINS . "
            WHERE a.tutor_id = :tid ORDER BY a.fecha DESC, a.created_at DESC
        ");
        $stmt->execute([':tid' => $tutorId]);
        return $stmt->fetchAll();
    }

    public function listarPorProyectos(array $proyectoIds): array
    {
        if (empty($proyectoIds)) return [];
        $placeholders = implode(',', array_fill(0, count($proyectoIds), '?'));
        $stmt = $this->db->prepare(self::SELECT_CON_JOINS . "
            WHERE a.proyecto_id IN ($placeholders) ORDER BY a.fecha DESC, a.created_at DESC
        ");
        $stmt->execute($proyectoIds);
        return $stmt->fetchAll();
    }

    public function insertar(array $data): int
    {
        $stmt = $this->db->prepare("
            INSERT INTO actividades (proyecto_id, tutor_id, fecha, descripcion)
            VALUES (:pid, :tid, :fecha, :desc)
        ");
        $stmt->execute([
            ':pid'   => $data['proyecto_id'],
            ':tid'   => $data['tutor_id'],
            ':fecha' => $data['fecha'],
            ':desc'  => $data['descripcion'],
        ]);
        return (int) $this->db->lastInsertId();
    }

    public function eliminar(int $id, int $tutorId): bool
    {
        $stmt = $this->db->prepare("DELETE FROM actividades WHERE id_actividad = :id AND tutor_id = :tid");
        $stmt->execute([':id' => $id, ':tid' => $tutorId]);
        return $stmt->rowCount() > 0;
    }
}

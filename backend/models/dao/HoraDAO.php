<?php

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../dto/HoraDTO.php';

class HoraDAO
{
    private PDO $db;

    private const SELECT_CON_JOINS = "
        SELECT h.id_hora as id,
               CONCAT(e.nombre, ' ', e.apellido) as estudiante,
               e.id_usuario as id_estudiante,
               p.nombre as proyecto,
               h.proyecto_id as id_proyecto,
               CONCAT(t.nombre, ' ', t.apellido) as tutor,
               h.tutor_id as id_tutor,
               h.actividad_id as id_actividad,
               h.fecha_actividad,
               h.descripcion,
               h.horas,
               h.estado
        FROM horas_vinculacion h
        JOIN usuarios e ON h.estudiante_id = e.id_usuario
        JOIN proyectos p ON h.proyecto_id = p.id_proyecto
        JOIN usuarios t ON h.tutor_id = t.id_usuario
    ";

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    public function listarPorEstudiante(int $estudianteId): array
    {
        $stmt = $this->db->prepare(self::SELECT_CON_JOINS . "
            WHERE h.estudiante_id = :eid ORDER BY h.fecha_actividad DESC
        ");
        $stmt->execute([':eid' => $estudianteId]);
        return $stmt->fetchAll();
    }

    public function listarPorTutor(int $tutorId): array
    {
        $stmt = $this->db->prepare(self::SELECT_CON_JOINS . "
            WHERE h.tutor_id = :tid ORDER BY h.fecha_actividad DESC
        ");
        $stmt->execute([':tid' => $tutorId]);
        return $stmt->fetchAll();
    }

    public function listarTodas(): array
    {
        $stmt = $this->db->query(self::SELECT_CON_JOINS . " ORDER BY h.fecha_actividad DESC");
        return $stmt->fetchAll();
    }

    public function obtenerResumen(int $estudianteId): array
    {
        $stmt = $this->db->prepare("
            SELECT
                COALESCE(SUM(CASE WHEN estado = 'aprobada' THEN horas ELSE 0 END), 0) as horas_aprobadas,
                COALESCE(SUM(CASE WHEN estado = 'pendiente' THEN horas ELSE 0 END), 0) as horas_pendientes,
                COALESCE(SUM(CASE WHEN estado = 'rechazada' THEN horas ELSE 0 END), 0) as horas_rechazadas,
                COUNT(*) as totalregistros
            FROM horas_vinculacion
            WHERE estudiante_id = :eid
        ");
        $stmt->execute([':eid' => $estudianteId]);
        return $stmt->fetch();
    }

    public function verificarDuplicada(int $estudianteId, int $proyectoId, string $fecha): bool
    {
        $stmt = $this->db->prepare("
            SELECT COUNT(*) FROM horas_vinculacion
            WHERE estudiante_id = :eid AND proyecto_id = :pid AND fecha_actividad = :fecha AND estado = 'pendiente'
        ");
        $stmt->execute([':eid' => $estudianteId, ':pid' => $proyectoId, ':fecha' => $fecha]);
        return $stmt->fetchColumn() > 0;
    }

    public function insertar(array $data): int
    {
        $actividadId = $data['actividad_id'] ?? null;
        $stmt = $this->db->prepare("
            INSERT INTO horas_vinculacion (estudiante_id, proyecto_id, tutor_id, actividad_id, fecha_actividad, descripcion, horas, estado)
            VALUES (:eid, :pid, :tid, :aid, :fecha, :desc, :horas, 'pendiente')
        ");
        $stmt->execute([
            ':eid'   => $data['estudiante_id'],
            ':pid'   => $data['proyecto_id'],
            ':tid'   => $data['tutor_id'],
            ':aid'   => $actividadId,
            ':fecha' => $data['fecha_actividad'],
            ':desc'  => $data['descripcion'],
            ':horas' => $data['horas'],
        ]);
        return (int) $this->db->lastInsertId();
    }

    public function obtenerPendientePorTutor(int $id, int $tutorId): ?array
    {
        $stmt = $this->db->prepare("
            SELECT h.id_hora, h.proyecto_id
            FROM horas_vinculacion h
            WHERE h.id_hora = :id AND h.estado = 'pendiente'
            AND h.tutor_id = :tid
        ");
        $stmt->execute([':id' => $id, ':tid' => $tutorId]);
        return $stmt->fetch() ?: null;
    }

    public function actualizarEstado(int $id, string $nuevoEstado): bool
    {
        $stmt = $this->db->prepare("UPDATE horas_vinculacion SET estado = :estado WHERE id_hora = :id");
        $stmt->execute([':estado' => $nuevoEstado, ':id' => $id]);
        return $stmt->rowCount() > 0;
    }

    public function contarPendientesPorTutor(int $tutorId): int
    {
        $stmt = $this->db->prepare("
            SELECT COUNT(*) FROM horas_vinculacion
            WHERE tutor_id = :tid AND estado = 'pendiente'
        ");
        $stmt->execute([':tid' => $tutorId]);
        return (int) $stmt->fetchColumn();
    }

    public function contarAprobadasPorTutor(int $tutorId): int
    {
        $stmt = $this->db->prepare("
            SELECT COUNT(*) FROM horas_vinculacion
            WHERE tutor_id = :tid AND estado = 'aprobada'
        ");
        $stmt->execute([':tid' => $tutorId]);
        return (int) $stmt->fetchColumn();
    }

    public function contarEstudiantesAsignados(int $tutorId): int
    {
        $stmt = $this->db->prepare("
            SELECT COUNT(DISTINCT estudiante_id) FROM horas_vinculacion
            WHERE tutor_id = :tid
        ");
        $stmt->execute([':tid' => $tutorId]);
        return (int) $stmt->fetchColumn();
    }
}

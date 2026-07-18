<?php

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../dto/SolicitudDTO.php';

class SolicitudDAO
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    public function listar(): array
    {
        $stmt = $this->db->query("
            SELECT s.id_solicitud as id,
                   CONCAT(e.nombre, ' ', e.apellido) as estudiante,
                   e.correo as estudiante_email,
                   s.proyecto_id as id_proyecto,
                   p.nombre as nombre_proyecto,
                   s.estado,
                   s.fecha_solicitud
            FROM solicitudes s
            JOIN usuarios e ON s.estudiante_id = e.id_usuario
            JOIN proyectos p ON s.proyecto_id = p.id_proyecto
            ORDER BY s.fecha_solicitud DESC
        ");
        return $stmt->fetchAll();
    }

    public function verificarDuplicada(int $estudianteId, int $proyectoId): bool
    {
        $stmt = $this->db->prepare("
            SELECT COUNT(*) FROM solicitudes
            WHERE estudiante_id = :eid AND proyecto_id = :pid
            AND estado IN ('pendiente', 'aceptada')
        ");
        $stmt->execute([':eid' => $estudianteId, ':pid' => $proyectoId]);
        return $stmt->fetchColumn() > 0;
    }

    public function insertar(int $estudianteId, int $proyectoId): int
    {
        $stmt = $this->db->prepare("
            INSERT INTO solicitudes (estudiante_id, proyecto_id, estado, fecha_solicitud)
            VALUES (:eid, :pid, 'pendiente', NOW())
        ");
        $stmt->execute([':eid' => $estudianteId, ':pid' => $proyectoId]);
        return (int) $this->db->lastInsertId();
    }

    public function obtenerPendiente(int $id): ?array
    {
        $stmt = $this->db->prepare("
            SELECT s.*, p.nombre as nombre_proyecto
            FROM solicitudes s
            JOIN proyectos p ON s.proyecto_id = p.id_proyecto
            WHERE s.id_solicitud = :id AND s.estado = 'pendiente'
        ");
        $stmt->execute([':id' => $id]);
        return $stmt->fetch() ?: null;
    }

    public function actualizarEstado(int $id, string $nuevoEstado): bool
    {
        $stmt = $this->db->prepare("UPDATE solicitudes SET estado = :estado WHERE id_solicitud = :id");
        $stmt->execute([':estado' => $nuevoEstado, ':id' => $id]);
        return $stmt->rowCount() > 0;
    }

    public function verificarAceptada(int $estudianteId, int $proyectoId): bool
    {
        $stmt = $this->db->prepare("
            SELECT id_solicitud FROM solicitudes
            WHERE estudiante_id = :eid AND proyecto_id = :pid AND estado = 'aceptada'
        ");
        $stmt->execute([':eid' => $estudianteId, ':pid' => $proyectoId]);
        return (bool) $stmt->fetch();
    }

    public function contarPorEstudiante(int $estudianteId): int
    {
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM solicitudes WHERE estudiante_id = :eid");
        $stmt->execute([':eid' => $estudianteId]);
        return (int) $stmt->fetchColumn();
    }

    public function contarAceptadasPorEstudiante(int $estudianteId): int
    {
        $stmt = $this->db->prepare(
            "SELECT COUNT(*) FROM solicitudes WHERE estudiante_id = :eid AND estado = 'aceptada'"
        );
        $stmt->execute([':eid' => $estudianteId]);
        return (int) $stmt->fetchColumn();
    }

    public function reset(): void
    {
        $this->db->exec("DELETE FROM solicitudes");
    }
}

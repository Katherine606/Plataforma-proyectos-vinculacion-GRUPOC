<?php

require_once __DIR__ . '/../../config/database.php';

class DashboardDAO
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    public function datosEstudiante(int $estudianteId): array
    {
        $stmt = $this->db->prepare("
            SELECT
                COALESCE(SUM(CASE WHEN h.estado = 'aprobada' THEN h.horas ELSE 0 END), 0) as horas_aprobadas,
                COALESCE(SUM(CASE WHEN h.estado = 'pendiente' THEN h.horas ELSE 0 END), 0) as horas_pendientes
            FROM horas_vinculacion h
            WHERE h.estudiante_id = :eid
        ");
        $stmt->execute([':eid' => $estudianteId]);
        $horas = $stmt->fetch();

        $stmt2 = $this->db->prepare("SELECT COUNT(*) FROM solicitudes WHERE estudiante_id = :eid");
        $stmt2->execute([':eid' => $estudianteId]);
        $totalSolicitudes = (int) $stmt2->fetchColumn();

        $stmt3 = $this->db->prepare(
            "SELECT COUNT(*) FROM solicitudes WHERE estudiante_id = :eid AND estado = 'aceptada'"
        );
        $stmt3->execute([':eid' => $estudianteId]);
        $solicitudesAceptadas = (int) $stmt3->fetchColumn();

        return array_merge($horas, [
            'total_solicitudes'      => $totalSolicitudes,
            'solicitudes_aceptadas'  => $solicitudesAceptadas,
        ]);
    }

    public function datosTutor(int $tutorId): array
    {
        $stmt = $this->db->prepare("
            SELECT
                (SELECT COUNT(DISTINCT estudiante_id) FROM horas_vinculacion WHERE tutor_id = :tid)
                    as estudiantes_asignados,
                (SELECT COUNT(*) FROM horas_vinculacion WHERE tutor_id = :tid AND estado = 'pendiente')
                    as horas_pendientes,
                (SELECT COUNT(*) FROM horas_vinculacion WHERE tutor_id = :tid AND estado = 'aprobada')
                    as horas_aprobadas,
                (SELECT COUNT(*) FROM proyectos WHERE tutor_id = :tid)
                    as proyectos_asignados
        ");
        $stmt->execute([':tid' => $tutorId]);
        return $stmt->fetch();
    }

    public function datosCoordinador(): array
    {
        $stmt = $this->db->query("
            SELECT
                (SELECT COUNT(*) FROM proyectos WHERE estado = 'activo') as total_proyectos,
                (SELECT COUNT(*) FROM usuarios WHERE rol_id = 3 AND estado = 'activo') as total_estudiantes,
                (SELECT COUNT(*) FROM solicitudes WHERE estado = 'pendiente') as solicitudes_pendientes,
                (SELECT COUNT(*) FROM solicitudes WHERE estado = 'aceptada') as solicitudes_aceptadas,
                (SELECT COALESCE(SUM(horas), 0) FROM horas_vinculacion WHERE estado = 'aprobada') as total_horas_aprobadas
        ");
        return $stmt->fetch();
    }
}

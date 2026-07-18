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
        try {
            $stmt = $this->db->prepare("
                SELECT
                    COALESCE(SUM(CASE WHEN h.estado = 'aprobada' THEN h.horas ELSE 0 END), 0) as horas_aprobadas,
                    COALESCE(SUM(CASE WHEN h.estado = 'pendiente' THEN h.horas ELSE 0 END), 0) as horas_pendientes
                FROM horas_vinculacion h
                WHERE h.estudiante_id = :eid
            ");
            $stmt->execute([':eid' => $estudianteId]);
            $horas = $stmt->fetch() ?: ['horas_aprobadas' => 0, 'horas_pendientes' => 0];
        } catch (Exception $e) {
            $horas = ['horas_aprobadas' => 0, 'horas_pendientes' => 0];
        }

        try {
            $stmt2 = $this->db->prepare("SELECT COUNT(*) FROM solicitudes WHERE estudiante_id = :eid");
            $stmt2->execute([':eid' => $estudianteId]);
            $totalSolicitudes = (int) $stmt2->fetchColumn();
        } catch (Exception $e) {
            $totalSolicitudes = 0;
        }

        try {
            $stmt3 = $this->db->prepare(
                "SELECT COUNT(*) FROM solicitudes WHERE estudiante_id = :eid AND estado = 'aceptada'"
            );
            $stmt3->execute([':eid' => $estudianteId]);
            $solicitudesAceptadas = (int) $stmt3->fetchColumn();
        } catch (Exception $e) {
            $solicitudesAceptadas = 0;
        }

        return [
            'horas_aprobadas'        => $horas['horas_aprobadas'] ?? 0,
            'horas_pendientes'       => $horas['horas_pendientes'] ?? 0,
            'total_solicitudes'      => $totalSolicitudes,
            'solicitudes_aceptadas'  => $solicitudesAceptadas,
        ];
    }

    public function datosTutor(int $tutorId): array
    {
        $resultado = [
            'estudiantes_asignados' => 0,
            'horas_pendientes'      => 0,
            'horas_aprobadas'       => 0,
            'proyectos_asignados'   => 0,
        ];

        try {
            $stmt = $this->db->prepare("
                SELECT COUNT(DISTINCT estudiante_id) as estudiantes_asignados
                FROM horas_vinculacion WHERE tutor_id = :tid
            ");
            $stmt->execute([':tid' => $tutorId]);
            $row = $stmt->fetch();
            $resultado['estudiantes_asignados'] = (int) ($row['estudiantes_asignados'] ?? 0);
        } catch (Exception $e) {}

        try {
            $stmt = $this->db->prepare("
                SELECT COUNT(*) as cnt FROM horas_vinculacion WHERE tutor_id = :tid AND estado = 'pendiente'
            ");
            $stmt->execute([':tid' => $tutorId]);
            $resultado['horas_pendientes'] = (int) $stmt->fetchColumn();
        } catch (Exception $e) {}

        try {
            $stmt = $this->db->prepare("
                SELECT COUNT(*) as cnt FROM horas_vinculacion WHERE tutor_id = :tid AND estado = 'aprobada'
            ");
            $stmt->execute([':tid' => $tutorId]);
            $resultado['horas_aprobadas'] = (int) $stmt->fetchColumn();
        } catch (Exception $e) {}

        try {
            $stmt = $this->db->prepare("
                SELECT COUNT(*) as cnt FROM proyectos WHERE tutor_id = :tid
            ");
            $stmt->execute([':tid' => $tutorId]);
            $resultado['proyectos_asignados'] = (int) $stmt->fetchColumn();
        } catch (Exception $e) {}

        return $resultado;
    }

    public function datosCoordinador(): array
    {
        $resultado = [
            'total_proyectos'           => 0,
            'total_estudiantes'         => 0,
            'solicitudes_pendientes'    => 0,
            'solicitudes_aceptadas'     => 0,
            'total_horas_aprobadas'     => 0,
        ];

        try {
            $stmt = $this->db->query("
                SELECT COUNT(*) as cnt FROM proyectos WHERE estado = 'activo'
            ");
            $resultado['total_proyectos'] = (int) $stmt->fetchColumn();
        } catch (Exception $e) {}

        try {
            $stmt = $this->db->query("
                SELECT COUNT(*) as cnt FROM usuarios WHERE rol_id = 3 AND estado = 'activo'
            ");
            $resultado['total_estudiantes'] = (int) $stmt->fetchColumn();
        } catch (Exception $e) {}

        try {
            $stmt = $this->db->query("
                SELECT COUNT(*) as cnt FROM solicitudes WHERE estado = 'pendiente'
            ");
            $resultado['solicitudes_pendientes'] = (int) $stmt->fetchColumn();
        } catch (Exception $e) {}

        try {
            $stmt = $this->db->query("
                SELECT COUNT(*) as cnt FROM solicitudes WHERE estado = 'aceptada'
            ");
            $resultado['solicitudes_aceptadas'] = (int) $stmt->fetchColumn();
        } catch (Exception $e) {}

        try {
            $stmt = $this->db->query("
                SELECT COALESCE(SUM(horas), 0) as cnt FROM horas_vinculacion WHERE estado = 'aprobada'
            ");
            $resultado['total_horas_aprobadas'] = (float) $stmt->fetchColumn();
        } catch (Exception $e) {}

        return $resultado;
    }
}

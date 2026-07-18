<?php

require_once __DIR__ . '/../models/dao/ProyectoDAO.php';
require_once __DIR__ . '/../models/dto/ProyectoDTO.php';
require_once __DIR__ . '/../models/dao/SolicitudDAO.php';

class ProyectoController
{
    private ProyectoDAO $dao;

    public function __construct()
    {
        $this->dao = new ProyectoDAO();
    }

    public function listar(): array
    {
        $filas = $this->dao->listar();
        $resultado = array_map(fn($row) => ProyectoDTO::desdeRegistro($row)->toArray(), $filas);
        return ['status' => 200, 'body' => $resultado];
    }

    public function obtenerPorId(int $id): array
    {
        $fila = $this->dao->obtenerPorId($id);
        if (!$fila) {
            return ['status' => 404, 'body' => ['error' => 'Proyecto no encontrado']];
        }
        return ['status' => 200, 'body' => ProyectoDTO::desdeRegistro($fila)->toArray()];
    }

    public function crear(array $body): array
    {
        $camposRequeridos = ['nombre', 'descripcion', 'tutor_id', 'facultad_id', 'carrera_id', 'cupos_max'];
        foreach ($camposRequeridos as $campo) {
            if (empty($body[$campo])) {
                return ['status' => 400, 'body' => ['error' => "El campo '$campo' es obligatorio"]];
            }
        }

        $cuposMax = min((int) $body['cupos_max'], 60);
        if ($cuposMax <= 0) {
            return ['status' => 400, 'body' => ['error' => 'Los cupos máximos deben ser mayores a 0']];
        }

        if (!$this->dao->verificarTutor((int) $body['tutor_id'])) {
            return ['status' => 400, 'body' => ['error' => 'El tutor seleccionado no es válido']];
        }

        $nuevoId = $this->dao->insertar($body);
        $proyecto = $this->dao->obtenerPorId($nuevoId);

        return ['status' => 201, 'body' => ProyectoDTO::desdeRegistro($proyecto)->toArray()];
    }

    public function actualizar(int $id, array $body): array
    {
        $existente = $this->dao->obtenerPorId($id);
        if (!$existente) {
            return ['status' => 404, 'body' => ['error' => 'Proyecto no encontrado']];
        }

        if (!$this->dao->actualizar($id, $body)) {
            return ['status' => 400, 'body' => ['error' => 'No se enviaron datos para actualizar']];
        }

        $proyecto = $this->dao->obtenerPorId($id);
        return ['status' => 200, 'body' => ProyectoDTO::desdeRegistro($proyecto)->toArray()];
    }

    public function eliminar(int $id): array
    {
        $existente = $this->dao->obtenerPorId($id);
        if (!$existente) {
            return ['status' => 404, 'body' => ['error' => 'Proyecto no encontrado']];
        }

        $this->dao->eliminar($id);
        return ['status' => 200, 'body' => ['ok' => true]];
    }

    public function detalle(int $id): array
    {
        $proyecto = $this->dao->obtenerPorId($id);
        if (!$proyecto) {
            return ['status' => 404, 'body' => ['error' => 'Proyecto no encontrado']];
        }

        $db = Database::getInstance()->getConnection();

        $stmt = $db->prepare("
            SELECT s.id_solicitud, u.id_usuario as estudiante_id,
                   CONCAT(u.nombre, ' ', u.apellido) as estudiante,
                   u.correo as email,
                   s.estado,
                   s.fecha_solicitud
            FROM solicitudes s
            JOIN usuarios u ON s.estudiante_id = u.id_usuario
            WHERE s.proyecto_id = :pid AND s.estado = 'aceptada'
            ORDER BY u.nombre
        ");
        $stmt->execute([':pid' => $id]);
        $estudiantes = $stmt->fetchAll();

        $stmt2 = $db->prepare("
            SELECT COUNT(*) FROM solicitudes
            WHERE proyecto_id = :pid AND estado = 'pendiente'
        ");
        $stmt2->execute([':pid' => $id]);
        $solicitudesPendientes = (int) $stmt2->fetchColumn();

        return ['status' => 200, 'body' => [
            'proyecto' => ProyectoDTO::desdeRegistro($proyecto)->toArray(),
            'estudiantes' => $estudiantes,
            'solicitudes_pendientes' => $solicitudesPendientes,
        ]];
    }

    public function sacarEstudiante(int $solicitudId): array
    {
        $db = Database::getInstance()->getConnection();
        $db->beginTransaction();
        try {
            $stmt = $db->prepare("
                SELECT s.* FROM solicitudes s
                WHERE s.id_solicitud = :id AND s.estado = 'aceptada'
            ");
            $stmt->execute([':id' => $solicitudId]);
            $solicitud = $stmt->fetch();

            if (!$solicitud) {
                $db->rollBack();
                return ['status' => 404, 'body' => ['error' => 'Solicitud no encontrada o el estudiante no esta aceptado.']];
            }

            $stmt2 = $db->prepare("UPDATE solicitudes SET estado = 'denegada' WHERE id_solicitud = :id");
            $stmt2->execute([':id' => $solicitudId]);

            $stmt3 = $db->prepare("
                UPDATE proyectos SET cupos_usados = GREATEST(cupos_usados - 1, 0)
                WHERE id_proyecto = :pid
            ");
            $stmt3->execute([':pid' => $solicitud['proyecto_id']]);

            $db->commit();
            return ['status' => 200, 'body' => ['ok' => true, 'mensaje' => 'Estudiante removido del proyecto.']];
        } catch (Exception $e) {
            $db->rollBack();
            return ['status' => 500, 'body' => ['error' => 'Error al remover estudiante.']];
        }
    }
}

<?php

require_once __DIR__ . '/../models/dao/ActividadDAO.php';
require_once __DIR__ . '/../models/dao/ProyectoDAO.php';
require_once __DIR__ . '/../models/dto/ActividadDTO.php';

class ActividadController
{
    private ActividadDAO $dao;
    private ProyectoDAO $proyectoDAO;

    public function __construct()
    {
        $this->dao = new ActividadDAO();
        $this->proyectoDAO = new ProyectoDAO();
    }

    public function listar(array $payload): array
    {
        $rol    = $payload['rol'] ?? '';
        $userId = (int) $payload['sub'];

        if ($rol === 'Tutor') {
            $filas = $this->dao->listarPorTutor($userId);
        } else {
            // Estudiante: listar actividades de sus proyectos aceptados
            $proyectoIds = $this->obtenerProyectosAceptados($userId);
            $filas = $this->dao->listarPorProyectos($proyectoIds);
        }

        $resultado = array_map(fn($row) => ActividadDTO::desdeRegistro($row)->toArray(), $filas);
        return ['status' => 200, 'body' => $resultado];
    }

    public function crear(int $tutorId, array $body): array
    {
        $idProyecto  = isset($body['proyecto_id']) ? (int) $body['proyecto_id'] : 0;
        $titulo      = trim($body['titulo'] ?? '');
        $fecha       = $body['fecha'] ?? '';
        $descripcion = trim($body['descripcion'] ?? '');

        if ($idProyecto <= 0 || empty($titulo) || empty($fecha) || empty($descripcion)) {
            return ['status' => 400, 'body' => ['error' => 'Todos los campos son obligatorios']];
        }

        if (strlen($titulo) > 200) {
            return ['status' => 400, 'body' => ['error' => 'El titulo no puede exceder 200 caracteres']];
        }

        // Verificar que el proyecto pertenezca al tutor
        $tutorIdReal = $this->proyectoDAO->obtenerTutorId($idProyecto);
        if ($tutorIdReal !== $tutorId) {
            return ['status' => 403, 'body' => ['error' => 'No tienes permiso sobre este proyecto']];
        }

        $nuevoId = $this->dao->insertar([
            'proyecto_id' => $idProyecto,
            'tutor_id'    => $tutorId,
            'titulo'      => $titulo,
            'fecha'       => $fecha,
            'descripcion' => $descripcion,
        ]);

        return ['status' => 201, 'body' => [
            'id'          => $nuevoId,
            'proyecto_id' => $idProyecto,
            'titulo'      => $titulo,
            'fecha'       => $fecha,
            'descripcion' => $descripcion,
        ]];
    }

    public function eliminar(int $id, int $tutorId): array
    {
        if (!$this->dao->eliminar($id, $tutorId)) {
            return ['status' => 404, 'body' => ['error' => 'Actividad no encontrada o sin permiso']];
        }
        return ['status' => 200, 'body' => ['ok' => true]];
    }

    public function detalleTutor(int $tutorId): array
    {
        $filas = $this->dao->detallePorTutor($tutorId);
        return ['status' => 200, 'body' => $filas];
    }

    public function estudiantesEnActividad(int $actividadId, int $tutorId): array
    {
        $filas = $this->dao->estudiantesPorActividad($actividadId);
        return ['status' => 200, 'body' => $filas];
    }

    private function obtenerProyectosAceptados(int $estudianteId): array
    {
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("
            SELECT proyecto_id FROM solicitudes
            WHERE estudiante_id = :eid AND estado = 'aceptada'
        ");
        $stmt->execute([':eid' => $estudianteId]);
        return array_map(fn($row) => (int) $row['proyecto_id'], $stmt->fetchAll());
    }
}

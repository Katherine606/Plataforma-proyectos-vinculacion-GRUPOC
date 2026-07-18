<?php

require_once __DIR__ . '/../models/dao/SolicitudDAO.php';
require_once __DIR__ . '/../models/dao/UsuarioDAO.php';
require_once __DIR__ . '/../models/dao/ProyectoDAO.php';
require_once __DIR__ . '/../models/dto/SolicitudDTO.php';

class SolicitudController
{
    private SolicitudDAO $solicitudDAO;
    private UsuarioDAO $usuarioDAO;
    private ProyectoDAO $proyectoDAO;

    public function __construct()
    {
        $this->solicitudDAO = new SolicitudDAO();
        $this->usuarioDAO   = new UsuarioDAO();
        $this->proyectoDAO  = new ProyectoDAO();
    }

    public function listar(?int $tutorId = null): array
    {
        $filas = $tutorId
            ? $this->solicitudDAO->listarPorTutor($tutorId)
            : $this->solicitudDAO->listar();
        $resultado = array_map(fn($row) => SolicitudDTO::desdeRegistro($row)->toArray(), $filas);
        return ['status' => 200, 'body' => $resultado];
    }

    public function crear(int $estudianteId, int $idProyecto): array
    {
        if ($idProyecto <= 0) {
            return ['status' => 400, 'body' => ['error' => 'id_proyecto inválido']];
        }

        // Verificar que el proyecto exista, tenga cupos y esté activo
        $proyecto = $this->proyectoDAO->obtenerPorId($idProyecto);
        if (!$proyecto) {
            return ['status' => 404, 'body' => ['error' => 'Proyecto no encontrado']];
        }
        if ($proyecto['cupos_usados'] >= $proyecto['cupos_max']) {
            return ['status' => 400, 'body' => ['error' => 'No hay cupos disponibles']];
        }
        if ($proyecto['estado'] !== 'activo') {
            return ['status' => 400, 'body' => ['error' => 'El proyecto no está activo']];
        }

        // Verificar duplicada
        if ($this->solicitudDAO->verificarDuplicada($estudianteId, $idProyecto)) {
            return ['status' => 400, 'body' => ['error' => 'Ya existe una solicitud abierta para este proyecto']];
        }

        // Un estudiante solo puede estar en 1 proyecto a la vez
        if ($this->solicitudDAO->tieneProyectoActivo($estudianteId)) {
            return ['status' => 400, 'body' => ['error' => 'Ya estás inscrito en un proyecto. Debes salir del actual primero.']];
        }

        // Crear solicitud con transacción
        $db = Database::getInstance()->getConnection();
        $db->beginTransaction();
        try {
            $nuevaId = $this->solicitudDAO->insertar($estudianteId, $idProyecto);
            $db->commit();

            return ['status' => 201, 'body' => [
                'id'              => $nuevaId,
                'id_proyecto'     => $idProyecto,
                'nombre_proyecto' => $proyecto['nombre'],
                'estado'          => 'pendiente'
            ]];
        } catch (Exception $e) {
            $db->rollBack();
            return ['status' => 500, 'body' => ['error' => 'Error al crear la solicitud']];
        }
    }

    public function gestionar(int $id, string $accion): array
    {
        $nuevoEstado = match ($accion) {
            'aceptar' => 'aceptada',
            'denegar' => 'denegada',
            default   => null,
        };

        if ($nuevoEstado === null) {
            return ['status' => 400, 'body' => ['error' => 'Acción inválida. Use accion=aceptar o accion=denegar']];
        }

        $solicitud = $this->solicitudDAO->obtenerPendiente($id);
        if (!$solicitud) {
            return ['status' => 404, 'body' => ['error' => 'Solicitud no encontrada o ya fue procesada']];
        }

        $db = Database::getInstance()->getConnection();
        $db->beginTransaction();
        try {
            $ok = $this->solicitudDAO->actualizarEstado($id, $nuevoEstado);
            if (!$ok) {
                $db->rollBack();
                return ['status' => 500, 'body' => ['error' => 'No se pudo actualizar el estado. Verifica el ENUM de la tabla solicitudes.']];
            }

            if ($nuevoEstado === 'aceptada') {
                if (!$this->proyectoDAO->incrementarCupos($solicitud['proyecto_id'])) {
                    $db->rollBack();
                    return ['status' => 400, 'body' => ['error' => 'No hay cupos disponibles']];
                }
            }

            $db->commit();

            return ['status' => 200, 'body' => [
                'id'              => $id,
                'estudiante'      => $solicitud['estudiante_id'],
                'id_proyecto'     => $solicitud['proyecto_id'],
                'nombre_proyecto' => $solicitud['nombre_proyecto'],
                'estado'          => $nuevoEstado
            ]];
        } catch (Exception $e) {
            $db->rollBack();
            return ['status' => 500, 'body' => ['error' => 'Error al procesar la solicitud']];
        }
    }

    public function reset(): array
    {
        $this->solicitudDAO->reset();
        return ['status' => 200, 'body' => ['ok' => true, 'mensaje' => 'Solicitudes reseteadas']];
    }
}

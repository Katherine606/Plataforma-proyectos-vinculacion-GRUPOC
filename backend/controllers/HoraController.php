<?php

require_once __DIR__ . '/../models/dao/HoraDAO.php';
require_once __DIR__ . '/../models/dao/UsuarioDAO.php';
require_once __DIR__ . '/../models/dao/ProyectoDAO.php';
require_once __DIR__ . '/../models/dto/HoraDTO.php';

class HoraController
{
    private HoraDAO $horaDAO;
    private UsuarioDAO $usuarioDAO;
    private ProyectoDAO $proyectoDAO;

    public function __construct()
    {
        $this->horaDAO    = new HoraDAO();
        $this->usuarioDAO = new UsuarioDAO();
        $this->proyectoDAO = new ProyectoDAO();
    }

    public function listar(array $payload): array
    {
        $rol     = $payload['rol'] ?? '';
        $correo  = $payload['correo'] ?? '';
        $user_id = $payload['sub'] ?? 0;

        $filas = match ($rol) {
            'Estudiante' => $this->horaDAO->listarPorEstudiante($user_id),
            'Tutor'      => $this->horaDAO->listarPorTutor($user_id),
            default      => $this->horaDAO->listarTodas(),
        };

        $resultado = array_map(fn($row) => HoraDTO::desdeRegistro($row)->toArray(), $filas);
        return ['status' => 200, 'body' => $resultado];
    }

    public function resumen(int $estudianteId): array
    {
        $resumen = $this->horaDAO->obtenerResumen($estudianteId);
        return ['status' => 200, 'body' => $resumen];
    }

    public function registrar(int $estudianteId, array $body): array
    {
        $idProyecto      = isset($body['id_proyecto']) ? (int) $body['id_proyecto'] : 0;
        $fechaActividad  = $body['fecha_actividad'] ?? '';
        $descripcion     = trim($body['descripcion'] ?? '');
        $horasVal        = isset($body['horas']) ? (float) $body['horas'] : 0;

        if ($idProyecto <= 0 || empty($fechaActividad) || empty($descripcion) || $horasVal <= 0) {
            return ['status' => 400, 'body' => ['error' => 'Todos los campos son obligatorios y las horas deben ser mayores a 0']];
        }

        if ($horasVal > 8) {
            return ['status' => 400, 'body' => ['error' => 'No se pueden registrar más de 8 horas por día']];
        }

        // Verificar que el estudiante esté aceptado en el proyecto
        if (!$this->solicitudAceptada($estudianteId, $idProyecto)) {
            return ['status' => 400, 'body' => ['error' => 'No estás aceptado en este proyecto']];
        }

        // Obtener tutor del proyecto
        $tutorId = $this->proyectoDAO->obtenerTutorId($idProyecto);
        if (!$tutorId) {
            return ['status' => 404, 'body' => ['error' => 'Proyecto no encontrado']];
        }

        // Verificar duplicado
        if ($this->horaDAO->verificarDuplicada($estudianteId, $idProyecto, $fechaActividad)) {
            return ['status' => 400, 'body' => ['error' => 'Ya existe un registro pendiente para esa fecha en este proyecto']];
        }

        $nuevoId = $this->horaDAO->insertar([
            'estudiante_id'    => $estudianteId,
            'proyecto_id'      => $idProyecto,
            'tutor_id'         => $tutorId,
            'actividad_id'     => isset($body['id_actividad']) ? (int) $body['id_actividad'] : null,
            'fecha_actividad'  => $fechaActividad,
            'descripcion'      => $descripcion,
            'horas'            => $horasVal,
        ]);

        return ['status' => 201, 'body' => [
            'id'              => $nuevoId,
            'id_proyecto'     => $idProyecto,
            'fecha_actividad' => $fechaActividad,
            'descripcion'     => $descripcion,
            'horas'           => $horasVal,
            'estado'          => 'pendiente'
        ]];
    }

    public function gestionar(int $id, string $accion, int $tutorId): array
    {
        $nuevoEstado = match ($accion) {
            'aprobar'  => 'aprobada',
            'rechazar' => 'rechazada',
            default    => null,
        };

        if ($nuevoEstado === null) {
            return ['status' => 400, 'body' => ['error' => 'Acción inválida. Use accion=aprobar o accion=rechazar']];
        }

        $hora = $this->horaDAO->obtenerPendientePorTutor($id, $tutorId);
        if (!$hora) {
            return ['status' => 404, 'body' => ['error' => 'Registro no encontrado, ya fue procesado, o no tienes permiso']];
        }

        $this->horaDAO->actualizarEstado($id, $nuevoEstado);

        return ['status' => 200, 'body' => [
            'id'       => $id,
            'estado'   => $nuevoEstado,
            'mensaje'  => $nuevoEstado === 'aprobada'
                ? 'Horas aprobadas correctamente'
                : 'Horas rechazadas'
        ]];
    }

    private function solicitudAceptada(int $estudianteId, int $proyectoId): bool
    {
        $solicitudDAO = new SolicitudDAO();
        return $solicitudDAO->verificarAceptada($estudianteId, $proyectoId);
    }
}

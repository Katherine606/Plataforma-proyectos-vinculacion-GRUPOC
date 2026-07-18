<?php

require_once __DIR__ . '/../models/dao/ProyectoDAO.php';
require_once __DIR__ . '/../models/dto/ProyectoDTO.php';

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
}

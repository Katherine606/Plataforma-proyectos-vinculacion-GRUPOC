<?php

require_once __DIR__ . '/../models/dao/UsuarioDAO.php';

class UsuariosController
{
    private UsuarioDAO $dao;

    public function __construct()
    {
        $this->dao = new UsuarioDAO();
    }

    public function listar(): array
    {
        $usuarios = $this->dao->listarTodos();
        return ['status' => 200, 'body' => $usuarios];
    }

    public function roles(): array
    {
        $roles = $this->dao->listarRoles();
        return ['status' => 200, 'body' => $roles];
    }

    public function crear(array $body): array
    {
        $requeridos = ['nombre', 'apellido', 'correo', 'password', 'rol_id'];
        foreach ($requeridos as $campo) {
            if (empty($body[$campo])) {
                return ['status' => 400, 'body' => ['error' => "El campo '$campo' es obligatorio."]];
            }
        }

        if (strlen($body['password']) < 6) {
            return ['status' => 400, 'body' => ['error' => 'La contraseña debe tener al menos 6 caracteres.']];
        }

        $existente = $this->dao->obtenerPorCorreo($body['correo']);
        if ($existente) {
            return ['status' => 400, 'body' => ['error' => 'Ya existe un usuario con ese correo.']];
        }

        $nuevoId = $this->dao->crear($body);
        return ['status' => 201, 'body' => ['ok' => true, 'id' => $nuevoId]];
    }

    public function actualizar(int $id, array $body): array
    {
        $existente = $this->dao->obtenerPorIdSimple($id);
        if (!$existente) {
            return ['status' => 404, 'body' => ['error' => 'Usuario no encontrado.']];
        }

        if (!empty($body['correo'])) {
            $otro = $this->dao->obtenerPorCorreo($body['correo']);
            if ($otro && (int)$otro['id'] !== $id) {
                return ['status' => 400, 'body' => ['error' => 'Ya existe otro usuario con ese correo.']];
            }
        }

        $this->dao->actualizar($id, $body);
        return ['status' => 200, 'body' => ['ok' => true]];
    }

    public function toggleEstado(int $id): array
    {
        $existente = $this->dao->obtenerPorIdSimple($id);
        if (!$existente) {
            return ['status' => 404, 'body' => ['error' => 'Usuario no encontrado.']];
        }

        $nuevoEstado = $existente['estado'] === 'activo' ? 'inactivo' : 'activo';
        $this->dao->cambiarEstado($id, $nuevoEstado);
        return ['status' => 200, 'body' => ['ok' => true, 'estado' => $nuevoEstado]];
    }

    public function eliminar(int $id): array
    {
        $existente = $this->dao->obtenerPorIdSimple($id);
        if (!$existente) {
            return ['status' => 404, 'body' => ['error' => 'Usuario no encontrado.']];
        }

        $this->dao->eliminar($id);
        return ['status' => 200, 'body' => ['ok' => true]];
    }
}

<?php

require_once __DIR__ . '/../models/dao/UsuarioDAO.php';
require_once __DIR__ . '/../models/dto/UsuarioDTO.php';

class PerfilController
{
    private UsuarioDAO $usuarioDAO;

    public function __construct()
    {
        $this->usuarioDAO = new UsuarioDAO();
    }

    public function obtener(array $payload): array
    {
        $userId = (int) $payload['sub'];
        $usuario = $this->usuarioDAO->obtenerPorId($userId);

        if (!$usuario) {
            return ['status' => 404, 'body' => ['error' => 'Usuario no encontrado']];
        }

        return ['status' => 200, 'body' => $usuario];
    }

    public function cambiarPassword(array $payload, array $body): array
    {
        $userId = (int) $payload['sub'];
        $actual = $body['password_actual'] ?? '';
        $nueva  = $body['password_nueva'] ?? '';

        if ($actual === '' || $nueva === '') {
            return ['status' => 400, 'body' => ['error' => 'Debes ingresar tu contraseña actual y la nueva.']];
        }

        if (strlen($nueva) < 6) {
            return ['status' => 400, 'body' => ['error' => 'La nueva contraseña debe tener al menos 6 caracteres.']];
        }

        $ok = $this->usuarioDAO->cambiarPassword($userId, $actual, $nueva);
        if (!$ok) {
            return ['status' => 400, 'body' => ['error' => 'La contraseña actual es incorrecta.']];
        }

        return ['status' => 200, 'body' => ['mensaje' => 'Contraseña actualizada correctamente.']];
    }
}

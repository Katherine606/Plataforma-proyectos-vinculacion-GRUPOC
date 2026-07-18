<?php

require_once __DIR__ . '/../models/Auth.php';
require_once __DIR__ . '/../config/JwtHelper.php';

class AuthController
{
    private Auth $authModel;
    private JwtHelper $jwtHelper;

    public function __construct(Auth $authModel, JwtHelper $jwtHelper)
    {
        $this->authModel = $authModel;
        $this->jwtHelper = $jwtHelper;
    }

    public function login(array $body): array
    {
        $email = trim($body['email'] ?? '');
        $password = (string)($body['password'] ?? '');

        if ($email === '' || $password === '') {
            return [
                'status' => 400,
                'body' => ['error' => 'Email y password son obligatorios']
            ];
        }

        $usuario = $this->authModel->login($email, $password);
        if (!$usuario) {
            return [
                'status' => 401,
                'body' => ['error' => 'Credenciales inválidas']
            ];
        }

        $token = $this->jwtHelper->generarToken([
            'sub' => $usuario['id'],
            'correo' => $usuario['correo'],
            'nombre' => $usuario['nombre'],
            'apellido' => $usuario['apellido'],
            'rol_id' => $usuario['rol_id'],
            'rol' => $usuario['rol']
        ]);

        return [
            'status' => 200,
            'body' => [
                'token' => $token,
                'usuario' => $usuario
            ]
        ];
    }
}

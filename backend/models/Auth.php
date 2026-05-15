<?php

class Auth
{
    private string $rutaUsuarios;

    public function __construct(string $rutaUsuarios)
    {
        $this->rutaUsuarios = $rutaUsuarios;
        $this->inicializarUsuariosSiNoExiste();
    }

    public function login(string $email, string $password): ?array
    {
        $usuarios = $this->leerUsuarios();

        foreach ($usuarios as $usuario) {
            if (($usuario['correo'] ?? '') !== $email) {
                continue;
            }

            if (!password_verify($password, $usuario['password'] ?? '')) {
                return null;
            }

            return [
                'id' => $usuario['id'] ?? null,
                'correo' => $usuario['correo'] ?? '',
                'rol_id' => $usuario['rol_id'] ?? null,
                'rol' => $usuario['rol'] ?? 'Estudiante'
            ];
        }

        return null;
    }

    private function inicializarUsuariosSiNoExiste(): void
    {
        if (file_exists($this->rutaUsuarios)) {
            return;
        }

        $usuariosIniciales = [
            [
                'id' => 1,
                'correo' => 'coordinador@ug.edu.ec',
                'password' => password_hash('Coord123*', PASSWORD_DEFAULT),
                'rol_id' => 1,
                'rol' => 'Coordinador'
            ],
            [
                'id' => 2,
                'correo' => 'tutor@ug.edu.ec',
                'password' => password_hash('Tutor123*', PASSWORD_DEFAULT),
                'rol_id' => 2,
                'rol' => 'Tutor'
            ],
            [
                'id' => 3,
                'correo' => 'estudiante@ug.edu.ec',
                'password' => password_hash('Est12345*', PASSWORD_DEFAULT),
                'rol_id' => 3,
                'rol' => 'Estudiante'
            ]
        ];

        file_put_contents(
            $this->rutaUsuarios,
            json_encode($usuariosIniciales, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)
        );
    }

    private function leerUsuarios(): array
    {
        $contenido = @file_get_contents($this->rutaUsuarios);
        $usuarios = json_decode($contenido ?: '[]', true);

        return is_array($usuarios) ? $usuarios : [];
    }
}

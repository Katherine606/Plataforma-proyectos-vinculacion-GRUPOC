<?php
/**
 * ====================================================================
 * Modelo de Autenticación 1
 * --------------------------------------------------------------------
 * Gestiona el login de usuarios consultando MySQL
 * ====================================================================
 */

require_once __DIR__ . '/../config/database.php';

class Auth
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    //Valida las credenciales del usuario contra MySQL y retorna los datos del usuario si es válido, null si no
    public function login(string $email, string $password): ?array
    {
        $stmt = $this->db->prepare("
            SELECT u.id_usuario, u.nombre, u.apellido, u.correo, u.password, u.rol_id, u.estado, r.nombre as rol
            FROM usuarios u JOIN roles r ON u.rol_id = r.id_rol WHERE u.correo = :correo LIMIT 1
        ");
        $stmt->execute([':correo' => $email]);
        $usuario = $stmt->fetch();

        if (!$usuario) {
            return null;
        }

        // Verificar que el usuario esté activo
        if ($usuario['estado'] !== 'activo') {
            return null;
        }

        // Verificar contraseña con bcrypt
        if (!password_verify($password, $usuario['password'])) {
            return null;
        }

        // Retornar datos del usuario (sin la contraseña)
        return [
            'id'       => $usuario['id_usuario'],
            'nombre'   => $usuario['nombre'],
            'apellido' => $usuario['apellido'],
            'correo'   => $usuario['correo'],
            'rol_id'   => $usuario['rol_id'],
            'rol'      => $usuario['rol']
        ];
    }
}

<?php

require_once __DIR__ . '/../../config/database.php';

class UsuarioDAO
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    public function obtenerPorCorreo(string $correo): ?array
    {
        $stmt = $this->db->prepare("
            SELECT id_usuario, nombre, apellido, correo, password, rol_id, estado, r.nombre as rol
            FROM usuarios u JOIN roles r ON u.rol_id = r.id_rol
            WHERE u.correo = :correo LIMIT 1
        ");
        $stmt->execute([':correo' => $correo]);
        return $stmt->fetch() ?: null;
    }

    public function obtenerIdPorCorreo(string $correo): ?int
    {
        $stmt = $this->db->prepare("SELECT id_usuario FROM usuarios WHERE correo = :correo LIMIT 1");
        $stmt->execute([':correo' => $correo]);
        $row = $stmt->fetch();
        return $row ? (int) $row['id_usuario'] : null;
    }

    public function listarTutoresActivos(): array
    {
        $stmt = $this->db->query("
            SELECT id_usuario as id, nombre, apellido, correo
            FROM usuarios WHERE rol_id = 2 AND estado = 'activo'
            ORDER BY nombre
        ");
        return $stmt->fetchAll();
    }

    public function listarFacultades(): array
    {
        $stmt = $this->db->query("SELECT id_facultad as id, nombre FROM facultades ORDER BY nombre");
        return $stmt->fetchAll();
    }

    public function listarCarreras(?int $facultadId = null): array
    {
        if ($facultadId) {
            $stmt = $this->db->prepare(
                "SELECT id_carrera as id, nombre FROM carreras WHERE facultad_id = :fid ORDER BY nombre"
            );
            $stmt->execute([':fid' => $facultadId]);
        } else {
            $stmt = $this->db->query("SELECT id_carrera as id, nombre, facultad_id FROM carreras ORDER BY nombre");
        }
        return $stmt->fetchAll();
    }

    public function obtenerPorId(int $id): ?array
    {
        $stmt = $this->db->prepare("
            SELECT u.id_usuario as id, u.nombre, u.apellido, u.correo, u.rol_id, u.estado,
                   r.nombre as rol
            FROM usuarios u JOIN roles r ON u.rol_id = r.id_rol
            WHERE u.id_usuario = :id LIMIT 1
        ");
        $stmt->execute([':id' => $id]);
        return $stmt->fetch() ?: null;
    }

    public function cambiarPassword(int $userId, string $passwordActual, string $passwordNueva): bool
    {
        $stmt = $this->db->prepare("SELECT password FROM usuarios WHERE id_usuario = :id");
        $stmt->execute([':id' => $userId]);
        $row = $stmt->fetch();

        if (!$row || !password_verify($passwordActual, $row['password'])) {
            return false;
        }

        $hash = password_hash($passwordNueva, PASSWORD_DEFAULT);
        $stmt = $this->db->prepare("UPDATE usuarios SET password = :pwd WHERE id_usuario = :id");
        $stmt->execute([':pwd' => $hash, ':id' => $userId]);
        return true;
    }

    public function listarTodos(): array
    {
        $stmt = $this->db->query("
            SELECT u.id_usuario as id, u.nombre, u.apellido, u.correo,
                   u.estado, r.id_rol as rol_id, r.nombre as rol
            FROM usuarios u
            JOIN roles r ON u.rol_id = r.id_rol
            ORDER BY r.nombre, u.nombre
        ");
        return $stmt->fetchAll();
    }

    public function listarRoles(): array
    {
        $stmt = $this->db->query("SELECT id_rol as id, nombre FROM roles ORDER BY id_rol");
        return $stmt->fetchAll();
    }

    public function obtenerPorIdSimple(int $id): ?array
    {
        $stmt = $this->db->prepare("SELECT id_usuario as id, nombre, apellido, correo, estado, rol_id FROM usuarios WHERE id_usuario = :id");
        $stmt->execute([':id' => $id]);
        return $stmt->fetch() ?: null;
    }

    public function crear(array $data): int
    {
        $hash = password_hash($data['password'], PASSWORD_DEFAULT);
        $stmt = $this->db->prepare("
            INSERT INTO usuarios (nombre, apellido, correo, password, rol_id, estado)
            VALUES (:nombre, :apellido, :correo, :password, :rol_id, 'activo')
        ");
        $stmt->execute([
            ':nombre'   => $data['nombre'],
            ':apellido' => $data['apellido'],
            ':correo'   => $data['correo'],
            ':password' => $hash,
            ':rol_id'   => (int) $data['rol_id'],
        ]);
        return (int) $this->db->lastInsertId();
    }

    public function actualizar(int $id, array $data): bool
    {
        $camposPermitidos = ['nombre', 'apellido', 'correo', 'rol_id'];
        $sets = [];
        $params = [':id' => $id];

        foreach ($camposPermitidos as $campo) {
            if (array_key_exists($campo, $data)) {
                $sets[] = "$campo = :$campo";
                $params[":$campo"] = $campo === 'rol_id' ? (int) $data[$campo] : $data[$campo];
            }
        }

        if (!empty($data['password'])) {
            $hash = password_hash($data['password'], PASSWORD_DEFAULT);
            $sets[] = 'password = :password';
            $params[':password'] = $hash;
        }

        if (empty($sets)) {
            return false;
        }

        $sql = "UPDATE usuarios SET " . implode(', ', $sets) . " WHERE id_usuario = :id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute($params);
    }

    public function cambiarEstado(int $id, string $estado): bool
    {
        $stmt = $this->db->prepare("UPDATE usuarios SET estado = :estado WHERE id_usuario = :id");
        return $stmt->execute([':estado' => $estado, ':id' => $id]);
    }

    public function eliminar(int $id): bool
    {
        $stmt = $this->db->prepare("DELETE FROM usuarios WHERE id_usuario = :id");
        return $stmt->execute([':id' => $id]);
    }
}

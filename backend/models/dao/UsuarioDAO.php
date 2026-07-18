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
}

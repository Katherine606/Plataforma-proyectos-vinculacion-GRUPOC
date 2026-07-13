<?php
/**
 * ====================================================================
 * Configuración de conexión a la base de datos MySQL
 * --------------------------------------------------------------------
 * Patrón Singleton para gestionar una sola instancia de conexión PDO.
 * Conecta a MySQL a través de XAMPP (localhost:3306).
 * ====================================================================
 */

class Database
{
    private static ?Database $instance = null;
    private PDO $connection;

    // Credenciales de conexión a MySQL
    private string $host = 'localhost';
    private string $dbname = 'vinculacion_db';
    private string $username = 'root';
    private string $password = '';

    private function __construct()
    {
        try { // Intenta establecer la conexión PDO
            $this->connection = new PDO(
                "mysql:host={$this->host};dbname={$this->dbname};charset=utf8mb4",
                $this->username,
                $this->password,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                ]
            );
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode([
                'error' => 'Error de conexión a la base de datos',
                'detalle' => $e->getMessage()
            ]);
            exit;
        }
    }

    //Obtiene la única instancia de la conexión (Singleton)
    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    //Retorna el objeto PDO de conexión
    public function getConnection(): PDO
    {
        return $this->connection;
    }
}

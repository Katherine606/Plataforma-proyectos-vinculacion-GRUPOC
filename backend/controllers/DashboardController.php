<?php

require_once __DIR__ . '/../models/dao/DashboardDAO.php';
require_once __DIR__ . '/../models/dao/UsuarioDAO.php';
require_once __DIR__ . '/../models/dao/HoraDAO.php';
require_once __DIR__ . '/../models/dao/ProyectoDAO.php';

class DashboardController
{
    private DashboardDAO $dao;
    private HoraDAO $horaDAO;
    private ProyectoDAO $proyectoDAO;

    public function __construct()
    {
        $this->dao        = new DashboardDAO();
        $this->horaDAO    = new HoraDAO();
        $this->proyectoDAO = new ProyectoDAO();
    }

    public function obtener(array $payload): array
    {
        $rol    = $payload['rol'] ?? '';
        $userId = $payload['sub'] ?? 0;

        $resultado = match ($rol) {
            'Estudiante'  => $this->dao->datosEstudiante($userId),
            'Tutor'       => $this->dao->datosTutor($userId),
            default       => $this->dao->datosCoordinador(),
        };

        return ['status' => 200, 'body' => $resultado];
    }
}

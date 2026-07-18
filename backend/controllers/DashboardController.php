<?php

require_once __DIR__ . '/../models/dao/DashboardDAO.php';

class DashboardController
{
    private DashboardDAO $dao;

    public function __construct()
    {
        $this->dao = new DashboardDAO();
    }

    public function obtener(array $payload): array
    {
        $rol    = $payload['rol'] ?? '';
        $userId = (int) ($payload['sub'] ?? 0);

        $rolLower = strtolower($rol);

        if (str_contains($rolLower, 'estudiante')) {
            $resultado = $this->dao->datosEstudiante($userId);
        } elseif (str_contains($rolLower, 'tutor')) {
            $resultado = $this->dao->datosTutor($userId);
        } else {
            $resultado = $this->dao->datosCoordinador();
        }

        return ['status' => 200, 'body' => $resultado];
    }
}

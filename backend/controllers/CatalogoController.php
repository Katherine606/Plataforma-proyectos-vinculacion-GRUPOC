<?php

require_once __DIR__ . '/../models/dao/UsuarioDAO.php';

class CatalogoController
{
    private UsuarioDAO $dao;

    public function __construct()
    {
        $this->dao = new UsuarioDAO();
    }

    public function listarTutores(): array
    {
        $tutores = $this->dao->listarTutoresActivos();
        return ['status' => 200, 'body' => $tutores];
    }

    public function listarFacultades(): array
    {
        $facultades = $this->dao->listarFacultades();
        return ['status' => 200, 'body' => $facultades];
    }

    public function listarCarreras(?int $facultadId): array
    {
        $carreras = $this->dao->listarCarreras($facultadId);
        return ['status' => 200, 'body' => $carreras];
    }
}

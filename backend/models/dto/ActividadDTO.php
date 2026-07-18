<?php

require_once __DIR__ . '/../../config/database.php';

class ActividadDTO
{
    public int $id;
    public int $idProyecto;
    public string $nombreProyecto;
    public int $idTutor;
    public string $tutor;
    public string $titulo;
    public string $fecha;
    public string $descripcion;
    public ?string $createdAt;

    public static function desdeRegistro(array $row): self
    {
        $dto = new self();
        $dto->id              = (int) ($row['id'] ?? $row['id_actividad']);
        $dto->idProyecto      = (int) ($row['id_proyecto'] ?? $row['proyecto_id']);
        $dto->nombreProyecto  = $row['nombre_proyecto'] ?? $row['proyecto'] ?? '';
        $dto->idTutor         = (int) ($row['id_tutor'] ?? $row['tutor_id']);
        $dto->tutor           = $row['tutor'] ?? '';
        $dto->titulo          = $row['titulo'] ?? '';
        $dto->fecha           = $row['fecha'];
        $dto->descripcion     = $row['descripcion'] ?? '';
        $dto->createdAt       = $row['created_at'] ?? null;
        return $dto;
    }

    public function toArray(): array
    {
        return [
            'id'              => $this->id,
            'id_proyecto'     => $this->idProyecto,
            'nombre_proyecto' => $this->nombreProyecto,
            'id_tutor'        => $this->idTutor,
            'tutor'           => $this->tutor,
            'titulo'          => $this->titulo,
            'fecha'           => $this->fecha,
            'descripcion'     => $this->descripcion,
            'created_at'      => $this->createdAt,
        ];
    }
}

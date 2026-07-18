<?php

class ProyectoDTO
{
    public int $id;
    public string $nombre;
    public string $descripcion;
    public string $tutor;
    public string $facultad;
    public string $carrera;
    public int $cuposMax;
    public int $cuposUsados;
    public string $estado;
    public ?string $fechaInicio;
    public ?string $fechaFin;

    public static function desdeRegistro(array $row): self
    {
        $dto = new self();
        $dto->id          = (int) ($row['id'] ?? $row['id_proyecto']);
        $dto->nombre      = $row['nombre'];
        $dto->descripcion = $row['descripcion'] ?? '';
        $dto->tutor       = $row['tutor'] ?? '';
        $dto->facultad    = $row['facultad'] ?? '';
        $dto->carrera     = $row['carrera'] ?? '';
        $dto->cuposMax    = (int) ($row['cupos_max'] ?? 0);
        $dto->cuposUsados = (int) ($row['cupos_usados'] ?? 0);
        $dto->estado      = $row['estado'] ?? 'activo';
        $dto->fechaInicio = $row['fecha_inicio'] ?? null;
        $dto->fechaFin    = $row['fecha_fin'] ?? null;
        return $dto;
    }

    public function toArray(): array
    {
        return [
            'id'           => $this->id,
            'nombre'       => $this->nombre,
            'descripcion'  => $this->descripcion,
            'tutor'        => $this->tutor,
            'facultad'     => $this->facultad,
            'carrera'      => $this->carrera,
            'cupos_max'    => $this->cuposMax,
            'cupos_usados' => $this->cuposUsados,
            'estado'       => $this->estado,
            'fecha_inicio' => $this->fechaInicio,
            'fecha_fin'    => $this->fechaFin,
        ];
    }
}

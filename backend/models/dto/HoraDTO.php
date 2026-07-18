<?php

class HoraDTO
{
    public int $id;
    public string $estudiante;
    public int $idEstudiante;
    public string $proyecto;
    public int $idProyecto;
    public string $tutor;
    public int $idTutor;
    public ?int $idActividad;
    public string $fechaActividad;
    public string $descripcion;
    public float $horas;
    public string $estado;

    public static function desdeRegistro(array $row): self
    {
        $dto = new self();
        $dto->id             = (int) ($row['id'] ?? $row['id_hora']);
        $dto->estudiante     = $row['estudiante'] ?? '';
        $dto->idEstudiante   = (int) ($row['id_estudiante'] ?? $row['estudiante_id']);
        $dto->proyecto       = $row['proyecto'] ?? '';
        $dto->idProyecto     = (int) ($row['id_proyecto'] ?? $row['proyecto_id']);
        $dto->tutor          = $row['tutor'] ?? '';
        $dto->idTutor        = (int) ($row['id_tutor'] ?? $row['tutor_id']);
        $dto->idActividad    = isset($row['id_actividad']) ? (int) $row['id_actividad'] : null;
        $dto->fechaActividad = $row['fecha_actividad'];
        $dto->descripcion    = $row['descripcion'] ?? '';
        $dto->horas          = (float) $row['horas'];
        $dto->estado         = $row['estado'];
        return $dto;
    }

    public function toArray(): array
    {
        return [
            'id'              => $this->id,
            'estudiante'      => $this->estudiante,
            'id_estudiante'   => $this->idEstudiante,
            'proyecto'        => $this->proyecto,
            'id_proyecto'     => $this->idProyecto,
            'tutor'           => $this->tutor,
            'id_tutor'        => $this->idTutor,
            'id_actividad'    => $this->idActividad,
            'fecha_actividad' => $this->fechaActividad,
            'descripcion'     => $this->descripcion,
            'horas'           => $this->horas,
            'estado'          => $this->estado,
        ];
    }
}

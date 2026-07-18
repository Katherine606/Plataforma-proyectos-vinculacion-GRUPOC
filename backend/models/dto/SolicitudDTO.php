<?php

class SolicitudDTO
{
    public int $id;
    public string $estudiante;
    public string $estudianteEmail;
    public int $idProyecto;
    public string $nombreProyecto;
    public string $estado;
    public ?string $fechaSolicitud;

    public static function desdeRegistro(array $row): self
    {
        $dto = new self();
        $dto->id              = (int) ($row['id'] ?? $row['id_solicitud']);
        $dto->estudiante      = $row['estudiante'] ?? '';
        $dto->estudianteEmail = $row['estudiante_email'] ?? '';
        $dto->idProyecto      = (int) ($row['id_proyecto'] ?? $row['proyecto_id']);
        $dto->nombreProyecto  = $row['nombre_proyecto'] ?? '';
        $dto->estado          = $row['estado'];
        $dto->fechaSolicitud  = $row['fecha_solicitud'] ?? null;
        return $dto;
    }

    public function toArray(): array
    {
        return [
            'id'              => $this->id,
            'estudiante'      => $this->estudiante,
            'estudiante_email'=> $this->estudianteEmail,
            'id_proyecto'     => $this->idProyecto,
            'nombre_proyecto' => $this->nombreProyecto,
            'estado'          => $this->estado,
            'fecha_solicitud' => $this->fechaSolicitud,
        ];
    }
}

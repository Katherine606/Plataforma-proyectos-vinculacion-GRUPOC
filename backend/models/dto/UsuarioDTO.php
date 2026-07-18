<?php

class UsuarioDTO
{
    public int $id;
    public string $nombre;
    public string $apellido;
    public string $correo;
    public string $rol;

    public static function desdeRegistro(array $row): self
    {
        $dto = new self();
        $dto->id       = (int) $row['id_usuario'];
        $dto->nombre   = $row['nombre'];
        $dto->apellido = $row['apellido'];
        $dto->correo   = $row['correo'];
        $dto->rol      = $row['rol'] ?? '';
        return $dto;
    }

    public function toArray(): array
    {
        return [
            'id'       => $this->id,
            'nombre'   => $this->nombre,
            'apellido' => $this->apellido,
            'correo'   => $this->correo,
            'rol'      => $this->rol,
        ];
    }
}

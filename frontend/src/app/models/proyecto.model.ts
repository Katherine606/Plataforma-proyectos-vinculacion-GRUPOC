// Modelo TypeScript que refleja la estructura del proyecto en el backend
// Cuando llegue MySQL, esta interfaz se mantiene igual

export interface Proyecto {
  id: number;
  nombre: string;
  descripcion: string;
  tutor: string;
  facultad: string;
  carrera: string;
  cupos_max: number;
  cupos_usados: number;
}

export interface Solicitud {
  id: number;
  estudiante: string;
  id_proyecto: number;
  nombre_proyecto: string;
  estado: 'pendiente' | 'aceptada' | 'denegada';
}

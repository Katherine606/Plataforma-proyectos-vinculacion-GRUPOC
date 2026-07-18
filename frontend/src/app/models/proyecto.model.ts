export interface Proyecto {
  id: number;
  nombre: string;
  descripcion: string;
  tutor: string;
  facultad: string;
  carrera: string;
  cupos_max: number;
  cupos_usados: number;
  estado?: string;
  fecha_inicio?: string;
  fecha_fin?: string;
}

export interface Solicitud {
  id: number;
  estudiante: string;
  id_proyecto: number;
  nombre_proyecto: string;
  estado: 'pendiente' | 'aceptada' | 'denegada';
  fecha_solicitud?: string;
}

export interface PaginacionResponse {
  datos: any[];
  total: number;
  pagina: number;
  por_pagina: number;
  total_paginas: number;
}

// Modelo TypeScript para el módulo de Horas de Vinculación
// Refleja la tabla horas_vinculacion en MySQL

export interface Hora {
  id: number;
  estudiante: string;
  id_estudiante: number;
  proyecto: string;
  id_proyecto: number;
  tutor: string;
  id_tutor: number;
  fecha_actividad: string;
  descripcion: string;
  horas: number;
  estado: 'pendiente' | 'aprobada' | 'rechazada';
  fecha_registro?: string;
}

export interface HorasResumen {
  horas_aprobadas: number;
  horas_pendientes: number;
  horas_rechazadas: number;
  totalregistros: number;
}

export interface Actividad {
  id: number;
  id_proyecto: number;
  nombre_proyecto: string;
  id_tutor: number;
  tutor: string;
  fecha: string;
  descripcion: string;
  created_at?: string;
}

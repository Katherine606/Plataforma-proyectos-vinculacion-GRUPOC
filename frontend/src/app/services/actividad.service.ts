import { Injectable } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { Observable } from 'rxjs';
import { Actividad } from '../models/actividad.model';

export interface ActividadDetalle {
  id: number;
  fecha: string;
  titulo: string;
  descripcion: string;
  nombre_proyecto: string;
  id_proyecto: number;
  total_registros: number;
  horas_aprobadas: number;
  horas_pendientes: number;
}

export interface EstudianteActividad {
  estudiante: string;
  fecha_actividad: string;
  horas: number;
  descripcion: string;
  estado: string;
}

@Injectable({
  providedIn: 'root'
})
export class ActividadService {

  private apiUrl = 'http://localhost:8000/index.php';

  constructor(private http: HttpClient) {}

  getActividades(): Observable<Actividad[]> {
    return this.http.get<Actividad[]>(`${this.apiUrl}?recurso=actividades`);
  }

  getDetalleTutor(): Observable<ActividadDetalle[]> {
    return this.http.get<ActividadDetalle[]>(`${this.apiUrl}?recurso=actividades&accion=detalle`);
  }

  getEstudiantesEnActividad(actividadId: number): Observable<EstudianteActividad[]> {
    return this.http.get<EstudianteActividad[]>(`${this.apiUrl}?recurso=actividades&accion=estudiantes&id=${actividadId}`);
  }

  crearActividad(datos: {
    proyecto_id: number;
    titulo: string;
    fecha: string;
    descripcion: string;
  }): Observable<Actividad> {
    return this.http.post<Actividad>(`${this.apiUrl}?recurso=actividades`, datos);
  }

  eliminarActividad(id: number): Observable<any> {
    return this.http.delete(`${this.apiUrl}?recurso=actividades&id=${id}`);
  }
}

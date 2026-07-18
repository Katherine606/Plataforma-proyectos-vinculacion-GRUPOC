import { Injectable } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { Observable } from 'rxjs';
import { Actividad } from '../models/actividad.model';

@Injectable({
  providedIn: 'root'
})
export class ActividadService {

  private apiUrl = 'http://localhost:8000/index.php';

  constructor(private http: HttpClient) {}

  getActividades(): Observable<Actividad[]> {
    return this.http.get<Actividad[]>(`${this.apiUrl}?recurso=actividades`);
  }

  crearActividad(datos: {
    proyecto_id: number;
    fecha: string;
    descripcion: string;
  }): Observable<Actividad> {
    return this.http.post<Actividad>(`${this.apiUrl}?recurso=actividades`, datos);
  }

  eliminarActividad(id: number): Observable<any> {
    return this.http.delete(`${this.apiUrl}?recurso=actividades&id=${id}`);
  }
}

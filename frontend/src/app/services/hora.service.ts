import { Injectable } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { Observable } from 'rxjs';
import { Hora, HorasResumen } from '../models/hora.model';

@Injectable({
  providedIn: 'root'
})
export class HoraService {

  private apiUrl = 'http://localhost:8000/index.php';

  constructor(private http: HttpClient) {}

  /** Obtiene las horas del estudiante logueado o del tutor (según rol) */
  getHoras(): Observable<Hora[]> {
    return this.http.get<Hora[]>(`${this.apiUrl}?recurso=horas`);
  }

  /** Registra una nueva entrada de horas (estudiante) */
  registrarHoras(datos: {
    id_proyecto: number;
    fecha_actividad: string;
    descripcion: string;
    horas: number;
  }): Observable<Hora> {
    return this.http.post<Hora>(`${this.apiUrl}?recurso=horas`, datos);
  }

  /** Aprueba un registro de horas (tutor) */
  aprobarHoras(id: number): Observable<Hora> {
    return this.http.put<Hora>(
      `${this.apiUrl}?recurso=horas&id=${id}&accion=aprobar`,
      {}
    );
  }

  /** Rechaza un registro de horas (tutor) */
  rechazarHoras(id: number): Observable<Hora> {
    return this.http.put<Hora>(
      `${this.apiUrl}?recurso=horas&id=${id}&accion=rechazar`,
      {}
    );
  }

  /** Obtiene el resumen de horas del estudiante */
  getResumenHoras(): Observable<HorasResumen> {
    return this.http.get<HorasResumen>(`${this.apiUrl}?recurso=horas&accion=resumen`);
  }
}

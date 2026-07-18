import { Injectable } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { Observable } from 'rxjs';
import { Proyecto, Solicitud } from '../models/proyecto.model';

@Injectable({
  providedIn: 'root'
})
export class ProyectoService {

  // URL base de la API PHP
  // IMPORTANTE: ajusta la ruta según donde tengas el proyecto en XAMPP/WAMP
  // FUTURO: cuando esté en servidor real, cambiar localhost por el dominio
  private apiUrl = 'http://localhost:8000/index.php';
  constructor(private http: HttpClient) {}

  // ─── PROYECTOS ──────────────────────────────────────────────────────────────

  /** Obtiene todos los proyectos
   *  FUTURO: SELECT * FROM proyectos */
  getProyectos(): Observable<Proyecto[]> {
    return this.http.get<Proyecto[]>(`${this.apiUrl}?recurso=proyectos`);
  }

  /** Crea un nuevo proyecto
   *  FUTURO: INSERT INTO proyectos (...) */
  crearProyecto(proyecto: Partial<Proyecto>): Observable<Proyecto> {
    return this.http.post<Proyecto>(`${this.apiUrl}?recurso=proyectos`, proyecto);
  }

  /** Actualiza un proyecto existente
   *  FUTURO: UPDATE proyectos SET ... WHERE id=? */
  actualizarProyecto(id: number, datos: Partial<Proyecto>): Observable<Proyecto> {
    return this.http.put<Proyecto>(`${this.apiUrl}?recurso=proyectos&id=${id}`, datos);
  }

  /** Elimina un proyecto
   *  FUTURO: DELETE FROM proyectos WHERE id=? */
  eliminarProyecto(id: number): Observable<any> {
    return this.http.delete<any>(`${this.apiUrl}?recurso=proyectos&id=${id}`);
  }

  // ─── SOLICITUDES ────────────────────────────────────────────────────────────

  /** Obtiene todas las solicitudes
   *  FUTURO: SELECT * FROM solicitudes */
  getSolicitudes(): Observable<Solicitud[]> {
    return this.http.get<Solicitud[]>(`${this.apiUrl}?recurso=solicitudes`);
  }

  /** Acepta una solicitud (y aumenta cupos_usados del proyecto)
   *  FUTURO: UPDATE solicitudes SET estado='aceptado' WHERE id=? */
  aceptarSolicitud(id: number): Observable<Solicitud> {
    return this.http.put<Solicitud>(
      `${this.apiUrl}?recurso=solicitudes&id=${id}&accion=aceptar`,
      {}
    );
  }

  /** Deniega una solicitud
   *  FUTURO: UPDATE solicitudes SET estado='denegado' WHERE id=? */
  denegarSolicitud(id: number): Observable<Solicitud> {
    return this.http.put<Solicitud>(
      `${this.apiUrl}?recurso=solicitudes&id=${id}&accion=denegar`,
      {}
    );
  }

  /** Solo para desarrollo: resetea solicitudes a pendiente */
  resetSolicitudes(): Observable<any> {
    return this.http.post<any>(`${this.apiUrl}?recurso=solicitudes&accion=reset`, {});
  }

  /** Crea una nueva solicitud de inscripción (estudiante) */
  crearSolicitud(idProyecto: number): Observable<Solicitud> {
    return this.http.post<Solicitud>(`${this.apiUrl}?recurso=solicitudes`, { id_proyecto: idProyecto });
  }

  /** Detalle de un proyecto con estudiantes inscritos */
  getDetalleProyecto(id: number): Observable<any> {
    return this.http.get<any>(`${this.apiUrl}?recurso=proyectos&id=${id}`);
  }

  /** Coordinador saca a un estudiante del proyecto */
  sacarEstudiante(solicitudId: number): Observable<any> {
    return this.http.put<any>(`${this.apiUrl}?recurso=proyectos&id=${solicitudId}&accion=sacar-estudiante`, {});
  }
}

import { Component, OnInit, ChangeDetectorRef } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { HttpErrorResponse } from '@angular/common/http';
import { ProyectoService } from '../../services/proyecto.service';
import { Solicitud } from '../../models/proyecto.model';
import { AuthService } from '../../services/auth.service';

@Component({
  selector: 'app-solicitudes',
  standalone: true,
  imports: [CommonModule, FormsModule],
  templateUrl: './solicitudes.component.html',
  styleUrl: './solicitudes.component.css'
})
export class SolicitudesComponent implements OnInit {

  todasLasSolicitudes: Solicitud[] = [];
  solicitudes: Solicitud[] = [];
  cargando = true;
  rol = '';
  canManageSolicitudes = false;
  errorMsg = '';
  buscarTexto = '';

  constructor(
    private proyectoService: ProyectoService,
    private authService: AuthService,
    private cdr: ChangeDetectorRef
  ) {}

  ngOnInit(): void {
    this.rol = this.authService.getRole() ?? '';
    const rolLower = this.rol.toLowerCase();
    this.canManageSolicitudes = rolLower.includes('tutor') || rolLower.includes('coordinador');
    this.cargar();
  }

  cargar(): void {
    this.cargando = true;
    this.errorMsg = '';
    this.proyectoService.getSolicitudes().subscribe({
      next: (data) => {
        this.todasLasSolicitudes = data;
        this.aplicarFiltro();
        this.cargando = false;
        this.cdr.detectChanges();
      },
      error: (err: HttpErrorResponse) => {
        console.error('Error al cargar solicitudes:', err);
        this.errorMsg = err.status === 403
          ? 'No tienes permisos para consultar solicitudes con tu rol actual.'
          : 'No se pudieron cargar las solicitudes.';
        this.cargando = false;
        this.cdr.detectChanges();
      }
    });
  }

  aplicarFiltro(): void {
    const texto = this.buscarTexto.toLowerCase().trim();
    if (!texto) {
      this.solicitudes = this.todasLasSolicitudes;
    } else {
      this.solicitudes = this.todasLasSolicitudes.filter(s =>
        s.estudiante.toLowerCase().includes(texto) ||
        s.nombre_proyecto.toLowerCase().includes(texto) ||
        s.estado.toLowerCase().includes(texto)
      );
    }
    this.cdr.detectChanges();
  }

  get pendientes(): Solicitud[] {
    return this.solicitudes.filter(s => s.estado === 'pendiente');
  }

  get procesadas(): Solicitud[] {
    return this.solicitudes.filter(s => s.estado !== 'pendiente');
  }

  get procesadasPorProyecto(): Array<{
    idProyecto: number;
    nombreProyecto: string;
    solicitudes: Solicitud[];
    totalAceptadas: number;
    totalDenegadas: number;
  }> {
    const mapa = new Map<number, { nombreProyecto: string; solicitudes: Solicitud[] }>();

    for (const solicitud of this.procesadas) {
      const actual = mapa.get(solicitud.id_proyecto);
      if (!actual) {
        mapa.set(solicitud.id_proyecto, {
          nombreProyecto: solicitud.nombre_proyecto,
          solicitudes: [solicitud]
        });
        continue;
      }

      actual.solicitudes.push(solicitud);
    }

    return Array.from(mapa.entries())
      .map(([idProyecto, data]) => {
        const totalAceptadas = data.solicitudes.filter(s => s.estado === 'aceptada').length;
        const totalDenegadas = data.solicitudes.filter(s => s.estado === 'denegada').length;

        return {
          idProyecto,
          nombreProyecto: data.nombreProyecto,
          solicitudes: data.solicitudes.sort((a, b) => a.estudiante.localeCompare(b.estudiante)),
          totalAceptadas,
          totalDenegadas
        };
      })
      .sort((a, b) => a.nombreProyecto.localeCompare(b.nombreProyecto));
  }

  get asignacionesPorProyecto(): Array<{
    idProyecto: number;
    nombreProyecto: string;
    estudiantes: string[];
  }> {
    const mapa = new Map<number, { nombreProyecto: string; estudiantes: string[] }>();

    for (const solicitud of this.solicitudes) {
      if (solicitud.estado !== 'aceptada') {
        continue;
      }

      const actual = mapa.get(solicitud.id_proyecto);
      if (!actual) {
        mapa.set(solicitud.id_proyecto, {
          nombreProyecto: solicitud.nombre_proyecto,
          estudiantes: [solicitud.estudiante]
        });
        continue;
      }

      if (!actual.estudiantes.includes(solicitud.estudiante)) {
        actual.estudiantes.push(solicitud.estudiante);
      }
    }

    return Array.from(mapa.entries())
      .map(([idProyecto, data]) => ({
        idProyecto,
        nombreProyecto: data.nombreProyecto,
        estudiantes: data.estudiantes
      }))
      .sort((a, b) => a.nombreProyecto.localeCompare(b.nombreProyecto));
  }

  confirmarAccion(accion: 'aceptar' | 'denegar', s: Solicitud): void {
    if (!this.canManageSolicitudes) {
      return;
    }

    this.errorMsg = '';

    const mensaje = accion === 'aceptar'
      ? `¿Estás seguro de que quieres aceptar la solicitud de ${s.estudiante} para el proyecto "${s.nombre_proyecto}"?`
      : `¿Estás seguro de que quieres denegar la solicitud de ${s.estudiante} para el proyecto "${s.nombre_proyecto}"?`;

    if (!confirm(mensaje)) return;

    const obs = accion === 'aceptar'
      ? this.proyectoService.aceptarSolicitud(s.id)
      : this.proyectoService.denegarSolicitud(s.id);

    obs.subscribe({
      next: () => this.cargar(),
      error: (err: HttpErrorResponse) => {
        this.errorMsg = err.status === 403
          ? 'Acceso denegado: tu rol no puede gestionar solicitudes.'
          : `No se pudo ${accion} la solicitud.`;
        console.error(`Error al ${accion}:`, err);
        this.cdr.detectChanges();
      }
    });
  }

  resetearSolicitudes(): void {
    if (!this.rol.toLowerCase().includes('coordinador')) {
      return;
    }

    this.errorMsg = '';

    if (!confirm('¿Resetear solicitudes a estado pendiente?')) return;
    this.proyectoService.resetSolicitudes().subscribe({
      next: () => this.cargar(),
      error: (err: HttpErrorResponse) => {
        this.errorMsg = err.status === 403
          ? 'Acceso denegado: solo Coordinador puede resetear solicitudes.'
          : 'No se pudo resetear solicitudes.';
        console.error('Error al resetear:', err);
        this.cdr.detectChanges();
      }
    });
  }
}
import { Component, OnInit, ChangeDetectorRef } from '@angular/core';
import { CommonModule } from '@angular/common';
import { RouterLink } from '@angular/router';
import { FormsModule } from '@angular/forms';
import { HttpClient } from '@angular/common/http';
import { ProyectoService } from '../../services/proyecto.service';
import { Proyecto } from '../../models/proyecto.model';
import { AuthService } from '../../services/auth.service';

interface Tutor {
  id: number;
  nombre: string;
  apellido: string;
}

@Component({
  selector: 'app-lista-proyectos',
  standalone: true,
  imports: [CommonModule, RouterLink, FormsModule],
  templateUrl: './lista-proyectos.component.html',
  styleUrl: './lista-proyectos.component.css'
})
export class ListaProyectosComponent implements OnInit {

  todosLosProyectos: Proyecto[] = [];
  proyectos: Proyecto[] = [];
  cargando = true;
  error = '';
  mensajeExito = '';
  rol = '';
  isEstudiante = false;
  isCoordinador = false;

  buscarTexto = '';
  paginaActual = 1;
  porPagina = 8;

  modalAbierto = false;
  proyectoEditando: Proyecto | null = null;
  editNombre = '';
  editDescripcion = '';
  editTutorId: number | null = null;
  editTutorBloqueado = true;
  editCuposMax: number = 0;
  editFechaInicio = '';
  editFechaFin = '';
  tutores: Tutor[] = [];
  erroresEdicion: { [campo: string]: string } = {};

  private readonly apiUrl = 'http://localhost:8000/index.php';

  constructor(
    private proyectoService: ProyectoService,
    private authService: AuthService,
    private http: HttpClient,
    private cdr: ChangeDetectorRef
  ) {}

  ngOnInit(): void {
    this.rol = this.authService.getRole() ?? '';
    const rolLower = this.rol.toLowerCase();
    this.isCoordinador = rolLower.includes('coordinador');
    this.isEstudiante = rolLower.includes('estudiante');
    this.cargar();
    if (this.isCoordinador) {
      this.cargarTutores();
    }
  }

  cargar(): void {
    this.cargando = true;
    this.error = '';
    this.proyectoService.getProyectos().subscribe({
      next: (data) => {
        this.todosLosProyectos = data;
        this.aplicarFiltro();
        this.cargando = false;
        this.cdr.detectChanges();
      },
      error: () => {
        this.error = 'No se pudo cargar la lista.';
        this.cargando = false;
        this.cdr.detectChanges();
      }
    });
  }

  cargarTutores(): void {
    this.http.get<Tutor[]>(`${this.apiUrl}?recurso=tutores`).subscribe({
      next: (data) => { this.tutores = data; this.cdr.detectChanges(); },
      error: () => {}
    });
  }

  // ─── BUSQUEDA / FILTRO ────────────────────────────────────────────────────
  aplicarFiltro(): void {
    const texto = this.buscarTexto.toLowerCase().trim();
    let filtrados = this.todosLosProyectos;

    if (texto) {
      filtrados = filtrados.filter(p =>
        p.nombre.toLowerCase().includes(texto) ||
        p.tutor.toLowerCase().includes(texto) ||
        p.facultad.toLowerCase().includes(texto) ||
        p.carrera.toLowerCase().includes(texto) ||
        p.descripcion.toLowerCase().includes(texto)
      );
    }

    this.paginaActual = 1;
    this.proyectos = filtrados;
    this.cdr.detectChanges();
  }

  get proyectosPaginados(): Proyecto[] {
    const inicio = (this.paginaActual - 1) * this.porPagina;
    return this.proyectos.slice(inicio, inicio + this.porPagina);
  }

  get totalPaginas(): number {
    return Math.max(1, Math.ceil(this.proyectos.length / this.porPagina));
  }

  get paginasArray(): number[] {
    const arr: number[] = [];
    for (let i = 1; i <= this.totalPaginas; i++) arr.push(i);
    return arr;
  }

  irPagina(p: number): void {
    if (p >= 1 && p <= this.totalPaginas) {
      this.paginaActual = p;
    }
  }

  // ─── ACCIONES ─────────────────────────────────────────────────────────────
  inscribirse(proyecto: Proyecto): void {
    this.mensajeExito = '';
    this.error = '';
    this.proyectoService.crearSolicitud(proyecto.id).subscribe({
      next: () => {
        this.mensajeExito = 'Solicitud enviada correctamente.';
        this.cdr.detectChanges();
      },
      error: (err) => {
        this.error = err?.error?.error ?? 'No se pudo enviar la solicitud';
        this.cdr.detectChanges();
      }
    });
  }

  confirmarEliminar(p: Proyecto): void {
    if (confirm(`Eliminar el proyecto "${p.nombre}"?`)) {
      this.proyectoService.eliminarProyecto(p.id).subscribe({
        next: () => this.cargar(),
        error: (err) => {
          this.error = err?.error?.error ?? 'No se pudo eliminar.';
          this.cdr.detectChanges();
        }
      });
    }
  }

  abrirEditor(p: Proyecto): void {
    this.proyectoEditando = p;
    this.editNombre = p.nombre;
    this.editDescripcion = p.descripcion;
    this.editCuposMax = p.cupos_max;
    this.editFechaInicio = p.fecha_inicio ?? '';
    this.editFechaFin = p.fecha_fin ?? '';
    const tutorEncontrado = this.tutores.find(
      t => `${t.nombre} ${t.apellido}` === p.tutor
    );
    this.editTutorId = tutorEncontrado?.id ?? null;
    this.editTutorBloqueado = true;
    this.modalAbierto = true;
  }

  cerrarModal(): void {
    this.modalAbierto = false;
    this.proyectoEditando = null;
  }

  habilitarTutor(): void {
    this.editTutorBloqueado = false;
  }

  guardarCambios(): void {
    if (!this.proyectoEditando) return;
    this.error = '';
    this.erroresEdicion = {};

    if (!this.editNombre.trim()) this.erroresEdicion['nombre'] = 'El nombre es obligatorio.';
    if (!this.editDescripcion.trim()) this.erroresEdicion['descripcion'] = 'La descripcion es obligatoria.';
    if (Object.keys(this.erroresEdicion).length > 0) {
      this.cdr.detectChanges();
      return;
    }

    const datos: any = {
      nombre: this.editNombre,
      descripcion: this.editDescripcion,
      fecha_inicio: this.editFechaInicio || null,
      fecha_fin: this.editFechaFin || null,
    };
    if (this.editTutorId) {
      datos.tutor_id = this.editTutorId;
    }

    this.proyectoService.actualizarProyecto(this.proyectoEditando.id, datos).subscribe({
      next: () => {
        this.cerrarModal();
        this.cargar();
      },
      error: (err) => {
        this.error = err?.error?.error ?? 'No se pudieron guardar los cambios.';
        this.cdr.detectChanges();
      }
    });
  }

  // ─── DETALLE ──────────────────────────────────────────────────────────────
  proyectoDetalle: any = null;
  detalleCargando = false;

  verDetalle(p: Proyecto): void {
    if (this.proyectoDetalle?.proyecto?.id === p.id) {
      this.cerrarDetalle();
      return;
    }
    this.detalleCargando = true;
    this.proyectoDetalle = null;
    this.proyectoService.getDetalleProyecto(p.id).subscribe({
      next: (data) => {
        this.proyectoDetalle = data;
        this.detalleCargando = false;
        this.cdr.detectChanges();
      },
      error: () => {
        this.detalleCargando = false;
        this.error = 'No se pudo cargar el detalle.';
        this.cdr.detectChanges();
      }
    });
  }

  cerrarDetalle(): void {
    this.proyectoDetalle = null;
  }

  sacarEstudiante(solicitudId: number): void {
    if (!confirm('Remover a este estudiante del proyecto?')) return;
    this.proyectoService.sacarEstudiante(solicitudId).subscribe({
      next: () => {
        if (this.proyectoDetalle) {
          this.proyectoService.getDetalleProyecto(this.proyectoDetalle.proyecto.id).subscribe({
            next: (data) => { this.proyectoDetalle = data; this.cdr.detectChanges(); },
            error: () => {}
          });
        }
        this.cargar();
      },
      error: (err) => {
        this.error = err?.error?.error ?? 'No se pudo remover al estudiante.';
        this.cdr.detectChanges();
      }
    });
  }
}

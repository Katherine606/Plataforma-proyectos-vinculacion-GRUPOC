import { Component, OnInit, ChangeDetectorRef } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { HttpErrorResponse } from '@angular/common/http';
import { ProyectoService } from '../../services/proyecto.service';
import { Proyecto } from '../../models/proyecto.model';
import { AuthService } from '../../services/auth.service';

@Component({
  selector: 'app-editor-proyectos',
  standalone: true,
  imports: [CommonModule, FormsModule],
  templateUrl: './editor-proyectos.component.html',
  styleUrl: './editor-proyectos.component.css'
})
export class EditorProyectosComponent implements OnInit {

  proyectos: Proyecto[] = [];
  filtroCarrera = '';

  modalAbierto = false;
  proyectoEditando: Proyecto | null = null;

  editNombre = '';
  editDescripcion = '';
  editTutor = '';
  editTutorBloqueado = true;

  private _origNombre = '';
  private _origDescripcion = '';
  private _origTutor = '';

  tutoresDisponibles = ['Ing. Juan Pérez', 'Ing. María López'];
  rol = '';
  canManageProjects = false;
  errorMsg = '';

  constructor(
    private proyectoService: ProyectoService,
    private authService: AuthService,
    private cdr: ChangeDetectorRef
  ) {}

  ngOnInit(): void {
    this.rol = this.authService.getRole() ?? '';
    this.canManageProjects = this.rol === 'Coordinador';
    this.cargarProyectos();
  }

  cargarProyectos(): void {
    this.errorMsg = '';
    this.proyectoService.getProyectos().subscribe({
      next: (data) => {
        this.proyectos = data;
        this.cdr.detectChanges();
      },
      error: (err: HttpErrorResponse) => {
        this.errorMsg = err.status === 403
          ? 'No tienes permisos para consultar proyectos con tu rol actual.'
          : 'No se pudieron cargar los proyectos. Intenta nuevamente.';
        console.error('Error al cargar proyectos:', err);
        this.cdr.detectChanges();
      }
    });
  }

  get proyectosFiltrados(): Proyecto[] {
    if (!this.filtroCarrera) return this.proyectos;
    return this.proyectos.filter(p => p.carrera === this.filtroCarrera);
  }

  confirmarEliminar(p: Proyecto): void {
    if (!this.canManageProjects) return;
    this.errorMsg = '';

    if (confirm(`¿Estás seguro de que quieres eliminar el proyecto "${p.nombre}"?`)) {
      this.proyectoService.eliminarProyecto(p.id).subscribe({
        next: () => this.cargarProyectos(),
        error: (err: HttpErrorResponse) => {
          this.errorMsg = err.status === 403
            ? 'Acceso denegado: tu rol no puede eliminar proyectos.'
            : 'No se pudo eliminar el proyecto.';
          console.error('Error al eliminar:', err);
          this.cdr.detectChanges();
        }
      });
    }
  }

  abrirEditor(p: Proyecto): void {
    if (!this.canManageProjects) return;

    this.proyectoEditando = p;
    this.editNombre = p.nombre;
    this.editDescripcion = p.descripcion;
    this.editTutor = p.tutor;
    this.editTutorBloqueado = true;
    this._origNombre = p.nombre;
    this._origDescripcion = p.descripcion;
    this._origTutor = p.tutor;
    this.modalAbierto = true;
  }

  cerrarModal(): void {
    this.modalAbierto = false;
    this.proyectoEditando = null;
  }

  revertirCambios(): void {
    if (confirm('¿Estás seguro de que quieres cancelar? Se perderán los cambios realizados.')) {
      this.editNombre = this._origNombre;
      this.editDescripcion = this._origDescripcion;
      this.editTutor = this._origTutor;
      this.editTutorBloqueado = true;
    }
  }

  habilitarTutor(): void {
    this.editTutorBloqueado = false;
  }

  guardarCambios(): void {
    if (!this.canManageProjects || !this.proyectoEditando) return;
    this.errorMsg = '';

    const datos: Partial<Proyecto> = {
      nombre: this.editNombre,
      descripcion: this.editDescripcion,
      tutor: this.editTutor
    };

    this.proyectoService.actualizarProyecto(this.proyectoEditando.id, datos).subscribe({
      next: () => {
        this.cerrarModal();
        this.cargarProyectos();
      },
      error: (err: HttpErrorResponse) => {
        this.errorMsg = err.status === 403
          ? 'Acceso denegado: tu rol no puede editar proyectos.'
          : 'No se pudo guardar los cambios del proyecto.';
        console.error('Error al actualizar:', err);
        this.cdr.detectChanges();
      }
    });
  }

  get cuposRestantes(): number {
    if (!this.proyectoEditando) return 0;
    return this.proyectoEditando.cupos_max - this.proyectoEditando.cupos_usados;
  }
}
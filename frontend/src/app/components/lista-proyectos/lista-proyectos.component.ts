import { Component, OnInit, ChangeDetectorRef } from '@angular/core';
import { CommonModule } from '@angular/common';
import { RouterLink } from '@angular/router';
import { FormsModule } from '@angular/forms';
import { HttpErrorResponse, HttpClient } from '@angular/common/http';
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

  proyectos: Proyecto[] = [];
  cargando = true;
  error = '';
  mensajeExito = '';
  rol = '';
  isEstudiante = false;
  isCoordinador = false;

  modalAbierto = false;
  proyectoEditando: Proyecto | null = null;
  editNombre = '';
  editDescripcion = '';
  editTutorId: number | null = null;
  editTutorBloqueado = true;
  editCuposMax: number = 0;
  tutores: Tutor[] = [];

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
        this.proyectos = data;
        this.cargando = false;
        this.cdr.detectChanges();
      },
      error: (err) => {
        this.error = 'No se pudo cargar la lista.';
        this.cargando = false;
        this.cdr.detectChanges();
      }
    });
  }

  cargarTutores(): void {
    this.http.get<Tutor[]>(`${this.apiUrl}?recurso=tutores`).subscribe({
      next: (data) => { this.tutores = data; this.cdr.detectChanges(); },
      error: () => console.error('Error al cargar tutores')
    });
  }

  getNombreTutor(tutorId: number): string {
    const t = this.tutores.find(t => t.id === tutorId);
    return t ? `${t.nombre} ${t.apellido}` : '';
  }

  inscribirse(proyecto: Proyecto): void {
    this.mensajeExito = '';
    this.error = '';
    this.proyectoService.crearSolicitud(proyecto.id).subscribe({
      next: (res) => {
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
    if (confirm(`¿Eliminar el proyecto "${p.nombre}"?`)) {
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

    const datos: any = {
      nombre: this.editNombre,
      descripcion: this.editDescripcion,
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
}

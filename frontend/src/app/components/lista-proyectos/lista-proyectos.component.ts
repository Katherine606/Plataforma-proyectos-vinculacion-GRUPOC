import { Component, OnInit, ChangeDetectorRef } from '@angular/core';
import { CommonModule } from '@angular/common';
import { RouterLink } from '@angular/router';
import { ProyectoService } from '../../services/proyecto.service';
import { Proyecto } from '../../models/proyecto.model';
import { AuthService } from '../../services/auth.service';

@Component({
  selector: 'app-lista-proyectos',
  standalone: true,
  imports: [CommonModule, RouterLink],
  templateUrl: './lista-proyectos.component.html',
  styleUrl: './lista-proyectos.component.css'
})
export class ListaProyectosComponent implements OnInit {

  proyectos: Proyecto[] = [];
  cargando = true;
  error = '';
  mensajeExito = '';
  rol = '';
  canManageProjects = false;

  constructor(
    private proyectoService: ProyectoService,
    private authService: AuthService,
    private cdr: ChangeDetectorRef
  ) {}

  ngOnInit(): void {
    this.rol = this.authService.getRole() ?? '';
    this.canManageProjects = this.rol === 'Coordinador';

    this.proyectoService.getProyectos().subscribe({
      next: (data) => {
        this.proyectos = data;
        this.cargando = false;
        this.cdr.detectChanges(); // Forzar actualización en modo zoneless
      },
      error: (err) => {
        console.error('Error al cargar proyectos:', err);
        this.error = 'No se pudo cargar la lista. Verifica que el servidor PHP esté corriendo.';
        this.cargando = false;
        this.cdr.detectChanges();
      }
    });
  }

  inscribirse(proyecto: Proyecto): void {
    this.mensajeExito = '';
    this.error = '';
    this.proyectoService.crearSolicitud(proyecto.id).subscribe({
      next: (res) => {
        this.mensajeExito = 'Solicitud enviada correctamente. Estado: ' + (res.estado ?? 'pendiente');
        this.cdr.detectChanges();
      },
      error: (err) => {
        console.error('Error al inscribirse:', err);
        this.error = err?.error?.error ?? 'No se pudo enviar la solicitud';
        this.cdr.detectChanges();
      }
    });
  }
}
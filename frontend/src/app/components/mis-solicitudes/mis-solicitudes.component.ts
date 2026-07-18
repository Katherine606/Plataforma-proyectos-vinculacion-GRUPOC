import { Component, OnInit, ChangeDetectorRef } from '@angular/core';
import { CommonModule } from '@angular/common';
import { ProyectoService } from '../../services/proyecto.service';
import { Solicitud } from '../../models/proyecto.model';

@Component({
  selector: 'app-mis-solicitudes',
  standalone: true,
  imports: [CommonModule],
  templateUrl: './mis-solicitudes.component.html',
  styleUrl: './mis-solicitudes.component.css'
})
export class MisSolicitudesComponent implements OnInit {

  solicitudes: Solicitud[] = [];
  cargando = true;
  errorMsg = '';

  constructor(
    private proyectoService: ProyectoService,
    private cdr: ChangeDetectorRef
  ) {}

  ngOnInit(): void {
    this.cargar();
  }

  cargar(): void {
    this.cargando = true;
    this.errorMsg = '';
    this.proyectoService.getSolicitudes().subscribe({
      next: (data) => {
        this.solicitudes = data;
        this.cargando = false;
        this.cdr.detectChanges();
      },
      error: () => {
        this.errorMsg = 'No se pudieron cargar tus solicitudes.';
        this.cargando = false;
        this.cdr.detectChanges();
      }
    });
  }

  get pendientes(): Solicitud[] {
    return this.solicitudes.filter(s => s.estado === 'pendiente');
  }

  get aceptadas(): Solicitud[] {
    return this.solicitudes.filter(s => s.estado === 'aceptada');
  }

  get denegadas(): Solicitud[] {
    return this.solicitudes.filter(s => s.estado === 'denegada');
  }

  badgeClass(estado: string): string {
    if (estado === 'aceptada') return 'badge-aceptado';
    if (estado === 'denegada') return 'badge-denegado';
    return 'badge-pendiente';
  }

  badgeLabel(estado: string): string {
    if (estado === 'aceptada') return 'Aceptada';
    if (estado === 'denegada') return 'Denegada';
    return 'Pendiente';
  }
}

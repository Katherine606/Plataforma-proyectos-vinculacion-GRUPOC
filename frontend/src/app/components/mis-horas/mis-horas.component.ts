import { Component, OnInit, ChangeDetectorRef } from '@angular/core';
import { CommonModule } from '@angular/common';
import { HoraService } from '../../services/hora.service';
import { Hora, HorasResumen } from '../../models/hora.model';

@Component({
  selector: 'app-mis-horas',
  standalone: true,
  imports: [CommonModule],
  templateUrl: './mis-horas.component.html',
  styleUrl: './mis-horas.component.css'
})
export class MisHorasComponent implements OnInit {

  horas: Hora[] = [];
  resumen: HorasResumen | null = null;
  cargando = true;
  errorMsg = '';

  constructor(
    private horaService: HoraService,
    private cdr: ChangeDetectorRef
  ) {}

  ngOnInit(): void {
    this.cargarDatos();
  }

  cargarDatos(): void {
    this.cargando = true;
    this.errorMsg = '';

    this.horaService.getHoras().subscribe({
      next: (data) => {
        this.horas = data;
        this.cdr.detectChanges();
      },
      error: (err) => {
        this.errorMsg = 'Error al cargar las horas.';
        console.error(err);
        this.cdr.detectChanges();
      }
    });

    this.horaService.getResumenHoras().subscribe({
      next: (data) => {
        this.resumen = data;
        this.cargando = false;
        this.cdr.detectChanges();
      },
      error: () => {
        this.cargando = false;
        this.cdr.detectChanges();
      }
    });
  }

  badgeClass(estado: string): string {
    switch (estado) {
      case 'aprobada':  return 'badge-aceptado';
      case 'rechazada': return 'badge-denegado';
      default:          return 'badge-pendiente';
    }
  }
}

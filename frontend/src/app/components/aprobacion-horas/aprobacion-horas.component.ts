import { Component, OnInit, ChangeDetectorRef } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { HoraService } from '../../services/hora.service';
import { Hora } from '../../models/hora.model';

@Component({
  selector: 'app-aprobacion-horas',
  standalone: true,
  imports: [CommonModule, FormsModule],
  templateUrl: './aprobacion-horas.component.html',
  styleUrl: './aprobacion-horas.component.css'
})
export class AprobacionHorasComponent implements OnInit {

  horasPendientes: Hora[] = [];
  horasProcesadas: Hora[] = [];
  todasLasHoras: Hora[] = [];
  cargando = true;
  errorMsg = '';
  exitoMsg = '';
  buscarTexto = '';

  constructor(
    private horaService: HoraService,
    private cdr: ChangeDetectorRef
  ) {}

  ngOnInit(): void {
    this.cargarHoras();
  }

  cargarHoras(): void {
    this.cargando = true;
    this.errorMsg = '';
    this.exitoMsg = '';

    this.horaService.getHoras().subscribe({
      next: (data) => {
        this.todasLasHoras = data;
        this.aplicarFiltro();
        this.cargando = false;
        this.cdr.detectChanges();
      },
      error: (err) => {
        this.errorMsg = 'Error al cargar las horas.';
        this.cargando = false;
        console.error(err);
        this.cdr.detectChanges();
      }
    });
  }

  aplicarFiltro(): void {
    const texto = this.buscarTexto.toLowerCase().trim();
    let filtradas = this.todasLasHoras;
    if (texto) {
      filtradas = filtradas.filter(h =>
        h.estudiante.toLowerCase().includes(texto) ||
        h.proyecto.toLowerCase().includes(texto) ||
        h.descripcion.toLowerCase().includes(texto)
      );
    }
    this.horasPendientes = filtradas.filter(h => h.estado === 'pendiente');
    this.horasProcesadas = filtradas.filter(h => h.estado !== 'pendiente');
    this.cdr.detectChanges();
  }

 aprobar(id: number): void {
    if (!confirm('¿Aprobar este registro de horas?')) return;

    this.horaService.aprobarHoras(id).subscribe({
      next: () => {
        this.exitoMsg = 'Horas aprobadas correctamente.';
        this.cargarHoras();
      },
      error: (err) => {
        this.errorMsg = err.error?.error || 'No se pudo aprobar.';
        this.cdr.detectChanges();
      }
    });
  }

  rechazar(id: number): void {
    if (!confirm('¿Rechazar este registro de horas?')) return;

    this.horaService.rechazarHoras(id).subscribe({
      next: () => {
        this.exitoMsg = 'Horas rechazadas.';
        this.cargarHoras();
      },
      error: (err) => {
        this.errorMsg = err.error?.error || 'No se pudo rechazar.';
        this.cdr.detectChanges();
      }
    });
  }
}

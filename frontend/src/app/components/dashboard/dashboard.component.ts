import { Component, OnInit, ChangeDetectorRef } from '@angular/core';
import { CommonModule } from '@angular/common';
import { RouterLink } from '@angular/router';
import { HttpClient } from '@angular/common/http';
import { AuthService } from '../../services/auth.service';

@Component({
  selector: 'app-dashboard',
  standalone: true,
  imports: [CommonModule, RouterLink],
  templateUrl: './dashboard.component.html',
  styleUrl: './dashboard.component.css'
})
export class DashboardComponent implements OnInit {

  datos: any = null;
  rol = '';
  nombre = '';
  cargando = true;
  errorMsg = '';

  private readonly apiUrl = 'http://localhost:8000/index.php';

  constructor(
    private http: HttpClient,
    private authService: AuthService,
    private cdr: ChangeDetectorRef
  ) {}

  ngOnInit(): void {
    this.rol = (this.authService.getRole() ?? '').toLowerCase();
    this.nombre = this.authService.getUsuario()?.nombre ?? '';
    this.cargarDashboard();
  }

  cargarDashboard(): void {
    this.cargando = true;
    this.http.get<any>(`${this.apiUrl}?recurso=dashboard`).subscribe({
      next: (data) => {
        this.datos = data;
        this.cargando = false;
        this.cdr.detectChanges();
      },
      error: () => {
        this.errorMsg = 'Error al cargar el dashboard.';
        this.cargando = false;
        this.cdr.detectChanges();
      }
    });
  }

  private pct(valor: number, total: number): number {
    return total > 0 ? Math.round((valor / total) * 100) : 0;
  }

  get porcentajeHoras(): number {
    if (!this.datos) return 0;
    const a = this.datos.horas_aprobadas ?? this.datos.total_horas_aprobadas ?? 0;
    const p = this.datos.horas_pendientes ?? 0;
    return this.pct(a, a + p);
  }

  get solicitudesPendientesPct(): number {
    if (!this.datos) return 0;
    return this.pct(this.datos.solicitudes_pendientes, this.datos.solicitudes_pendientes + this.datos.solicitudes_aceptadas);
  }

  get solicitudesAceptadasPct(): number {
    return 100 - this.solicitudesPendientesPct;
  }

  get tutorAprobadasPct(): number {
    if (!this.datos) return 0;
    return this.pct(this.datos.horas_aprobadas, this.datos.horas_aprobadas + this.datos.horas_pendientes);
  }

  get tutorPendientesPct(): number {
    return 100 - this.tutorAprobadasPct;
  }

  get estudiantePendientesPct(): number {
    if (!this.datos) return 0;
    return this.pct(this.datos.horas_pendientes, (this.datos.horas_aprobadas ?? 0) + this.datos.horas_pendientes);
  }

  get saludo(): string {
    const h = new Date().getHours();
    if (h < 12) return 'Buenos dias';
    if (h < 18) return 'Buenas tardes';
    return 'Buenas noches';
  }
}

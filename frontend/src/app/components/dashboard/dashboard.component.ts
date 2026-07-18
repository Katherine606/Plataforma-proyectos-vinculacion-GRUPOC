import { Component, OnInit, ChangeDetectorRef } from '@angular/core';
import { CommonModule } from '@angular/common';
import { HttpClient } from '@angular/common/http';
import { AuthService } from '../../services/auth.service';

@Component({
  selector: 'app-dashboard',
  standalone: true,
  imports: [CommonModule],
  templateUrl: './dashboard.component.html',
  styleUrl: './dashboard.component.css'
})
export class DashboardComponent implements OnInit {

  datos: any = null;
  rol = '';
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
      error: (err) => {
        this.errorMsg = 'Error al cargar el dashboard.';
        this.cargando = false;
        console.error(err);
        this.cdr.detectChanges();
      }
    });
  }
}

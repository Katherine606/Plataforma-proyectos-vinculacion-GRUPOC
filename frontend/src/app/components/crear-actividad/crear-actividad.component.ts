import { Component, OnInit, ChangeDetectorRef } from '@angular/core';
import { CommonModule } from '@angular/common';
import { ReactiveFormsModule, FormBuilder, FormGroup, Validators } from '@angular/forms';
import { HttpErrorResponse, HttpClient } from '@angular/common/http';
import { ActividadService } from '../../services/actividad.service';
import { Actividad } from '../../models/actividad.model';
import { Proyecto } from '../../models/proyecto.model';

@Component({
  selector: 'app-crear-actividad',
  standalone: true,
  imports: [CommonModule, ReactiveFormsModule],
  templateUrl: './crear-actividad.component.html',
  styleUrl: './crear-actividad.component.css'
})
export class CrearActividadComponent implements OnInit {

  form: FormGroup;
  proyectos: Proyecto[] = [];
  actividades: Actividad[] = [];
  enviando = false;
  exitoMsg = '';
  errorMsg = '';

  constructor(
    private fb: FormBuilder,
    private actividadService: ActividadService,
    private http: HttpClient,
    private cdr: ChangeDetectorRef
  ) {
    this.form = this.fb.group({
      proyecto_id:  [null, [Validators.required]],
      fecha:        ['', [Validators.required]],
      descripcion:  ['', [Validators.required, Validators.minLength(5)]],
    });
  }

  ngOnInit(): void {
    this.http.get<Proyecto[]>('http://localhost:8000/index.php?recurso=proyectos').subscribe({
      next: (data) => { this.proyectos = data; this.cdr.detectChanges(); },
      error: () => { console.error('Error al cargar proyectos'); }
    });
    this.cargarActividades();
  }

  cargarActividades(): void {
    this.actividadService.getActividades().subscribe({
      next: (data) => { this.actividades = data; this.cdr.detectChanges(); },
      error: (err) => { console.error(err); }
    });
  }

  onSubmit(): void {
    this.form.markAllAsTouched();
    if (this.form.invalid) return;

    this.enviando = true;
    this.exitoMsg = '';
    this.errorMsg = '';
    this.cdr.detectChanges();

    this.actividadService.crearActividad(this.form.value).subscribe({
      next: () => {
        this.exitoMsg = 'Actividad creada correctamente.';
        this.form.reset();
        this.enviando = false;
        this.cargarActividades();
        this.cdr.detectChanges();
      },
      error: (err: HttpErrorResponse) => {
        this.errorMsg = err.error?.error || 'No se pudo crear la actividad.';
        this.enviando = false;
        this.cdr.detectChanges();
      }
    });
  }

  eliminar(id: number): void {
    if (!confirm('¿Eliminar esta actividad?')) return;
    this.actividadService.eliminarActividad(id).subscribe({
      next: () => { this.cargarActividades(); },
      error: (err) => { this.errorMsg = err.error?.error || 'No se pudo eliminar.'; this.cdr.detectChanges(); }
    });
  }

  hoy(): string {
    return new Date().toISOString().split('T')[0];
  }
}

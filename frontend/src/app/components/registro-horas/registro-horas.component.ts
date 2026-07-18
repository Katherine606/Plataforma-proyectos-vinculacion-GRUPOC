import { Component, OnInit, ChangeDetectorRef } from '@angular/core';
import { CommonModule } from '@angular/common';
import { ReactiveFormsModule, FormBuilder, FormGroup, Validators } from '@angular/forms';
import { HttpErrorResponse, HttpClient } from '@angular/common/http';
import { HoraService } from '../../services/hora.service';
import { ActividadService } from '../../services/actividad.service';
import { Actividad } from '../../models/actividad.model';

@Component({
  selector: 'app-registro-horas',
  standalone: true,
  imports: [CommonModule, ReactiveFormsModule],
  templateUrl: './registro-horas.component.html',
  styleUrl: './registro-horas.component.css'
})
export class RegistroHorasComponent implements OnInit {

  form: FormGroup;
  actividades: Actividad[] = [];
  actividadesFiltradas: Actividad[] = [];
  proyectos: any[] = [];
  enviando = false;
  exitoMsg = '';
  errorMsg = '';

  constructor(
    private fb: FormBuilder,
    private horaService: HoraService,
    private actividadService: ActividadService,
    private http: HttpClient,
    private cdr: ChangeDetectorRef
  ) {
    this.form = this.fb.group({
      id_actividad:  [null, [Validators.required]],
      horas:         [null, [Validators.required, Validators.min(1), Validators.max(8)]],
    });
  }

  ngOnInit(): void {
    this.actividadService.getActividades().subscribe({
      next: (data) => {
        this.actividades = data;
        this.cdr.detectChanges();
      },
      error: () => { console.error('Error al cargar actividades'); }
    });
  }

  onActividadChange(): void {
    // No necesitamos filtrar, el estudiante ve todas las actividades de sus proyectos
  }

  onSubmit(): void {
    this.form.markAllAsTouched();
    if (this.form.invalid) return;

    const actividadSeleccionada = this.actividades.find(
      a => a.id === this.form.value.id_actividad
    );

    if (!actividadSeleccionada) {
      this.errorMsg = 'Selecciona una actividad válida.';
      return;
    }

    const datos = {
      id_actividad:    actividadSeleccionada.id,
      id_proyecto:     actividadSeleccionada.id_proyecto,
      fecha_actividad: actividadSeleccionada.fecha,
      descripcion:     actividadSeleccionada.descripcion,
      horas:           this.form.value.horas,
    };

    this.enviando = true;
    this.exitoMsg = '';
    this.errorMsg = '';
    this.cdr.detectChanges();

    this.horaService.registrarHoras(datos).subscribe({
      next: () => {
        this.exitoMsg = 'Horas registradas correctamente. Pendiente de aprobación del tutor.';
        this.form.reset();
        this.enviando = false;
        this.cdr.detectChanges();
      },
      error: (err: HttpErrorResponse) => {
        this.errorMsg = err.error?.error || 'No se pudieron registrar las horas.';
        this.enviando = false;
        this.cdr.detectChanges();
      }
    });
  }
}

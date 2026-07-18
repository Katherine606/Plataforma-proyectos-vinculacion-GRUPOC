import { Component, OnInit, ChangeDetectorRef } from '@angular/core';
import { CommonModule } from '@angular/common';
import { HttpErrorResponse } from '@angular/common/http';
import { RouterLink, Router } from '@angular/router';
import {
  FormBuilder, FormGroup, Validators, ReactiveFormsModule, AbstractControl, ValidationErrors
} from '@angular/forms';
import { ProyectoService } from '../../services/proyecto.service';
import { HttpClient } from '@angular/common/http';

interface Tutor {
  id: number;
  nombre: string;
  apellido: string;
  correo: string;
}

interface Facultad {
  id: number;
  nombre: string;
}

interface Carrera {
  id: number;
  nombre: string;
  facultad_id: number;
}

@Component({
  selector: 'app-formulario-proyecto',
  standalone: true,
  imports: [CommonModule, RouterLink, ReactiveFormsModule],
  templateUrl: './formulario-proyecto.component.html',
  styleUrl: './formulario-proyecto.component.css'
})
export class FormularioProyectoComponent implements OnInit {

  form: FormGroup;
  enviando = false;
  errorEnvio = '';

  tutores: Tutor[] = [];
  facultades: Facultad[] = [];
  carreras: Carrera[] = [];
  carrerasFiltradas: Carrera[] = [];

  private readonly apiUrl = 'http://localhost:8000/index.php';

  constructor(
    private fb: FormBuilder,
    private proyectoService: ProyectoService,
    private http: HttpClient,
    private router: Router,
    private cdr: ChangeDetectorRef
  ) {
    this.form = this.fb.group({
      nombre:       ['', [Validators.required, this.validarNombreConSentido]],
      descripcion:  ['', [Validators.required]],
      facultad_id:  [null, [Validators.required]],
      carrera_id:   [null, [Validators.required]],
      tutor_id:     [null, [Validators.required]],
      cupos_max:    [null, [Validators.required, Validators.min(1), Validators.max(60)]],
      fecha_inicio: [''],
      fecha_fin:    ['']
    });
  }

  ngOnInit(): void {
    this.cargarTutores();
    this.cargarFacultades();
    this.cargarCarreras();

    // Cuando cambia la facultad, filtrar las carreras
    this.form.get('facultad_id')?.valueChanges.subscribe(facultadId => {
      this.form.patchValue({ carrera_id: null });
      if (facultadId) {
        this.carrerasFiltradas = this.carreras.filter(c => c.facultad_id === facultadId);
      } else {
        this.carrerasFiltradas = [];
      }
    });
  }

  cargarTutores(): void {
    this.http.get<Tutor[]>(`${this.apiUrl}?recurso=tutores`).subscribe({
      next: (data) => { this.tutores = data; this.cdr.detectChanges(); },
      error: () => { console.error('Error al cargar tutores'); }
    });
  }

  cargarFacultades(): void {
    this.http.get<Facultad[]>(`${this.apiUrl}?recurso=facultades`).subscribe({
      next: (data) => { this.facultades = data; this.cdr.detectChanges(); },
      error: () => { console.error('Error al cargar facultades'); }
    });
  }

  cargarCarreras(): void {
    this.http.get<Carrera[]>(`${this.apiUrl}?recurso=carreras`).subscribe({
      next: (data) => { this.carreras = data; this.cdr.detectChanges(); },
      error: () => { console.error('Error al cargar carreras'); }
    });
  }

  validarNombreConSentido(control: AbstractControl): ValidationErrors | null {
    const valor: string = control.value || '';
    if (!valor.trim()) return null;

    if (!/^[a-zA-ZáéíóúÁÉÍÓÚñÑ\s]+$/.test(valor)) {
      return { soloLetras: true };
    }

    const palabras = valor.trim().split(/\s+/);
    if (palabras.length < 2) return { pocasPalabras: true };
    return null;
  }

  limpiarEntradaNombre(event: Event): void {
    const input = event.target as HTMLInputElement;
    const limpio = input.value.replace(/[^a-zA-ZáéíóúÁÉÍÓÚñÑ\s]/g, '');
    if (limpio !== input.value) {
      this.form.get('nombre')?.setValue(limpio);
    }
  }

  errorNombre(): string {
    const c = this.form.get('nombre');
    if (!c?.touched || !c.errors) return '';
    if (c.errors['required'])          return 'El nombre del proyecto es obligatorio.';
    if (c.errors['soloLetras'])        return 'Solo se permiten letras y espacios.';
    if (c.errors['pocasPalabras'])     return 'Ingresa al menos dos palabras.';
    return '';
  }

  errorRequerido(campo: string, msg: string): string {
    const c = this.form.get(campo);
    return (c?.touched && c.errors?.['required']) ? msg : '';
  }

  errorCupos(): string {
    const c = this.form.get('cupos_max');
    if (!c?.touched || !c.errors) return '';
    if (c.errors['required']) return 'Los cupos son obligatorios.';
    if (c.errors['min'] || c.errors['max']) return 'Los cupos deben ser entre 1 y 60.';
    return '';
  }

  onSubmit(): void {
    this.form.markAllAsTouched();
    if (this.form.invalid) return;

    this.enviando = true;
    this.errorEnvio = '';
    this.cdr.detectChanges();

    this.proyectoService.crearProyecto(this.form.value).subscribe({
      next: () => {
        this.router.navigate(['/proyectos']);
      },
      error: (err: HttpErrorResponse) => {
        console.error('Error al crear proyecto:', err);
        this.errorEnvio = err.status === 403
          ? 'Acceso denegado: tu rol no puede crear proyectos.'
          : (err.error?.error || 'No se pudo guardar. Verifica que el servidor PHP esté corriendo.');
        this.enviando = false;
        this.cdr.detectChanges();
      }
    });
  }

  confirmarCancelar(): void {
    if (confirm('¿Estás seguro de que quieres cancelar? Se perderán los datos ingresados.')) {
      this.form.reset({
        nombre: '',
        descripcion: '',
        facultad_id: null,
        carrera_id: null,
        tutor_id: null,
        cupos_max: null
      });
      this.carrerasFiltradas = [];
    }
  }
}

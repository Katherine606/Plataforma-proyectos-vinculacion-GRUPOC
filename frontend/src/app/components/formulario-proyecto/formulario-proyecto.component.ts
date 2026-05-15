import { Component, ChangeDetectorRef } from '@angular/core';
import { CommonModule } from '@angular/common';
import { HttpErrorResponse } from '@angular/common/http';
import { RouterLink, Router } from '@angular/router';
import {
  FormBuilder, FormGroup, Validators, ReactiveFormsModule, AbstractControl, ValidationErrors
} from '@angular/forms';
import { ProyectoService } from '../../services/proyecto.service';

@Component({
  selector: 'app-formulario-proyecto',
  standalone: true,
  imports: [CommonModule, RouterLink, ReactiveFormsModule],
  templateUrl: './formulario-proyecto.component.html',
  styleUrl: './formulario-proyecto.component.css'
})
export class FormularioProyectoComponent {

  form: FormGroup;
  enviando = false;
  errorEnvio = '';
  tutoresDisponibles = ['Ing. Juan Pérez', 'Ing. María López'];

  constructor(
    private fb: FormBuilder,
    private proyectoService: ProyectoService,
    private router: Router,
    private cdr: ChangeDetectorRef
  ) {
    this.form = this.fb.group({
      nombre:      ['', [Validators.required, this.validarNombreConSentido]],
      descripcion: ['', [Validators.required]],
      facultad:    ['', [Validators.required]],
      carrera:     ['', [Validators.required]],
      tutor:       ['', [Validators.required]],
      cupos_max:   [null, [Validators.required, Validators.min(1), Validators.max(60)]]
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
    if (c.errors['palabraSinSentido']) return 'Ingresa un nombre válido.';
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

    if (!this.tutoresDisponibles.includes(this.form.value.tutor ?? '')) {
      this.errorEnvio = 'Selecciona un tutor válido.';
      return;
    }

    this.enviando = true;
    this.errorEnvio = '';
    this.cdr.detectChanges();

    this.proyectoService.crearProyecto(this.form.value).subscribe({
      next: () => {
        // Navegar al editor después de guardar
        this.router.navigate(['/editor']);
      },
      error: (err: HttpErrorResponse) => {
        console.error('Error al crear proyecto:', err);
        this.errorEnvio = err.status === 403
          ? 'Acceso denegado: tu rol no puede crear proyectos.'
          : 'No se pudo guardar. Verifica que el servidor PHP esté corriendo.';
        this.enviando = false;
        this.cdr.detectChanges();
      }
    });
  }

  /** Cancelar: limpia campos pero deja los selects en "-- Seleccionar --" */
  confirmarCancelar(): void {
    if (confirm('¿Estás seguro de que quieres cancelar? Se perderán los datos ingresados.')) {
      // reset con valores explícitos para que los selects queden en "-- Seleccionar --"
      this.form.reset({
        nombre: '',
        descripcion: '',
        facultad: '',
        carrera: '',
        tutor: '',
        cupos_max: null
      });
    }
  }
}
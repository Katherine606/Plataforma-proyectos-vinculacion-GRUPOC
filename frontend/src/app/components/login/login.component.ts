import { Component } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormBuilder, FormGroup, ReactiveFormsModule, Validators } from '@angular/forms';
import { Router } from '@angular/router';
import { AuthService } from '../../services/auth.service';

@Component({
  selector: 'app-login',
  standalone: true,
  imports: [CommonModule, ReactiveFormsModule],
  templateUrl: './login.component.html',
  styleUrl: './login.component.css'
})
export class LoginComponent {
  cargando = false;
  error = '';
  form: FormGroup;

  constructor(
    private fb: FormBuilder,
    private authService: AuthService,
    private router: Router
  ) {
    this.form = this.fb.group({
      email: ['', [Validators.required, Validators.email]],
      password: ['', [Validators.required, Validators.minLength(6)]]
    });
  }

  onSubmit(): void {
    if (this.form.invalid) {
      this.form.markAllAsTouched();
      return;
    }

    this.cargando = true;
    this.error = '';

    const credentials = {
      email: this.form.value.email ?? '',
      password: this.form.value.password ?? ''
    };

    this.authService.login(credentials).subscribe({
      next: () => {
        this.cargando = false;
        this.redirigirSegunRol();
      },
      error: () => {
        this.cargando = false;
        this.error = 'Credenciales inválidas o servidor no disponible.';
      }
    });
  }

  private redirigirSegunRol(): void {
    const rol = this.authService.getRole();

    if (rol === 'Coordinador') {
      this.router.navigate(['/']);
      return;
    }

    if (rol === 'Tutor') {
      this.router.navigate(['/solicitudes']);
      return;
    }

    this.router.navigate(['/']);
  }
}

import { Component, OnInit, ChangeDetectorRef } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormBuilder, FormGroup, Validators, ReactiveFormsModule } from '@angular/forms';
import { HttpClient } from '@angular/common/http';
import { AuthService } from '../../services/auth.service';

@Component({
  selector: 'app-perfil',
  standalone: true,
  imports: [CommonModule, ReactiveFormsModule],
  templateUrl: './perfil.component.html',
  styleUrl: './perfil.component.css'
})
export class PerfilComponent implements OnInit {

  usuario: any = null;
  cargando = true;
  error = '';

  form: FormGroup;
  cambiando = false;
  exitoMsg = '';
  errorMsgPwd = '';

  private readonly apiUrl = 'http://localhost:8000/index.php';

  constructor(
    private fb: FormBuilder,
    private http: HttpClient,
    private authService: AuthService,
    private cdr: ChangeDetectorRef
  ) {
    this.form = this.fb.group({
      password_actual: ['', [Validators.required]],
      password_nueva:  ['', [Validators.required, Validators.minLength(6)]],
      password_confirmar: ['', [Validators.required]]
    }, { validators: this.validarPasswords });
  }

  ngOnInit(): void {
    this.http.get<any>(`${this.apiUrl}?recurso=perfil`).subscribe({
      next: (data) => {
        this.usuario = data;
        this.cargando = false;
        this.cdr.detectChanges();
      },
      error: () => {
        this.error = 'No se pudo cargar el perfil.';
        this.cargando = false;
        this.cdr.detectChanges();
      }
    });
  }

  validarPasswords(group: FormGroup): { [key: string]: boolean } | null {
    const nueva = group.get('password_nueva')?.value;
    const confirmar = group.get('password_confirmar')?.value;
    if (nueva && confirmar && nueva !== confirmar) {
      return { passwordsNoCoinciden: true };
    }
    return null;
  }

  onSubmitPassword(): void {
    this.form.markAllAsTouched();
    if (this.form.invalid) return;

    this.cambiando = true;
    this.exitoMsg = '';
    this.errorMsgPwd = '';
    this.cdr.detectChanges();

    this.http.put<any>(`${this.apiUrl}?recurso=perfil&accion=password`, {
      password_actual: this.form.value.password_actual,
      password_nueva: this.form.value.password_nueva
    }).subscribe({
      next: (res) => {
        this.exitoMsg = res.mensaje || 'Contraseña actualizada.';
        this.form.reset();
        this.cambiando = false;
        this.cdr.detectChanges();
      },
      error: (err) => {
        this.errorMsgPwd = err?.error?.error || 'No se pudo cambiar la contraseña.';
        this.cambiando = false;
        this.cdr.detectChanges();
      }
    });
  }

  get rolDisplay(): string {
    if (!this.usuario?.rol) return '';
    const r = this.usuario.rol.toLowerCase();
    if (r.includes('coordinador')) return 'Coordinador';
    if (r.includes('tutor')) return 'Tutor Académico';
    if (r.includes('estudiante')) return 'Estudiante';
    return this.usuario.rol;
  }
}

import { Component, OnInit, ChangeDetectorRef } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { HttpClient } from '@angular/common/http';

interface Usuario {
  id: number;
  nombre: string;
  apellido: string;
  correo: string;
  rol: string;
  rol_id: number;
  estado: string;
}

interface Rol {
  id: number;
  nombre: string;
}

@Component({
  selector: 'app-usuarios',
  standalone: true,
  imports: [CommonModule, FormsModule],
  templateUrl: './usuarios.component.html',
  styleUrl: './usuarios.component.css'
})
export class UsuariosComponent implements OnInit {

  usuarios: Usuario[] = [];
  roles: Rol[] = [];
  cargando = true;
  error = '';
  exito = '';

  modalAbierto = false;
  editando = false;
  usuarioSeleccionado: Usuario | null = null;
  erroresForm: { [campo: string]: string } = {};

  form = {
    nombre: '',
    apellido: '',
    correo: '',
    password: '',
    rol_id: 2
  };

  private readonly apiUrl = 'http://localhost:8000/index.php';

  constructor(
    private http: HttpClient,
    private cdr: ChangeDetectorRef
  ) {}

  ngOnInit(): void {
    this.cargar();
    this.cargarRoles();
  }

  cargar(): void {
    this.cargando = true;
    this.error = '';
    this.http.get<Usuario[]>(`${this.apiUrl}?recurso=usuarios`).subscribe({
      next: (data) => { this.usuarios = data; this.cargando = false; this.cdr.detectChanges(); },
      error: () => { this.error = 'No se pudieron cargar los usuarios.'; this.cargando = false; this.cdr.detectChanges(); }
    });
  }

  cargarRoles(): void {
    this.http.get<Rol[]>(`${this.apiUrl}?recurso=roles`).subscribe({
      next: (data) => { this.roles = data; this.cdr.detectChanges(); },
      error: () => {}
    });
  }

  abrirCrear(): void {
    this.editando = false;
    this.usuarioSeleccionado = null;
    this.form = { nombre: '', apellido: '', correo: '', password: '', rol_id: 2 };
    this.erroresForm = {};
    this.modalAbierto = true;
  }

  abrirEditar(u: Usuario): void {
    this.editando = true;
    this.usuarioSeleccionado = u;
    this.form = {
      nombre: u.nombre,
      apellido: u.apellido,
      correo: u.correo,
      password: '',
      rol_id: u.rol_id
    };
    this.erroresForm = {};
    this.modalAbierto = true;
  }

  cerrarModal(): void {
    this.modalAbierto = false;
    this.usuarioSeleccionado = null;
    this.erroresForm = {};
  }

  guardar(): void {
    this.error = '';
    this.exito = '';
    this.erroresForm = {};

    if (!this.form.nombre.trim()) this.erroresForm['nombre'] = 'El nombre es obligatorio.';
    if (!this.form.apellido.trim()) this.erroresForm['apellido'] = 'El apellido es obligatorio.';
    if (!this.form.correo.trim()) {
      this.erroresForm['correo'] = 'El correo es obligatorio.';
    } else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(this.form.correo)) {
      this.erroresForm['correo'] = 'Formato de correo invalido.';
    }
    if (!this.editando && !this.form.password) {
      this.erroresForm['password'] = 'La contraseña es obligatoria.';
    } else if (this.form.password && this.form.password.length < 6) {
      this.erroresForm['password'] = 'Minimo 6 caracteres.';
    }
    if (Object.keys(this.erroresForm).length > 0) {
      this.cdr.detectChanges();
      return;
    }

    if (this.editando && this.usuarioSeleccionado) {
      const datos: any = {
        nombre: this.form.nombre,
        apellido: this.form.apellido,
        correo: this.form.correo,
        rol_id: this.form.rol_id
      };
      if (this.form.password) {
        datos.password = this.form.password;
      }
      this.http.put<any>(`${this.apiUrl}?recurso=usuarios&id=${this.usuarioSeleccionado.id}`, datos).subscribe({
        next: () => { this.cerrarModal(); this.cargar(); this.exito = 'Usuario actualizado.'; this.cdr.detectChanges(); },
        error: (err) => { this.error = err?.error?.error ?? 'No se pudo actualizar.'; this.cdr.detectChanges(); }
      });
    } else {
      this.http.post<any>(`${this.apiUrl}?recurso=usuarios`, this.form).subscribe({
        next: () => { this.cerrarModal(); this.cargar(); this.exito = 'Usuario creado.'; this.cdr.detectChanges(); },
        error: (err) => { this.error = err?.error?.error ?? 'No se pudo crear.'; this.cdr.detectChanges(); }
      });
    }
  }

  toggleEstado(u: Usuario): void {
    const accion = u.estado === 'activo' ? 'desactivar' : 'activar';
    if (!confirm(`¿${accion.charAt(0).toUpperCase() + accion.slice(1)} al usuario ${u.nombre} ${u.apellido}?`)) return;

    this.http.put<any>(`${this.apiUrl}?recurso=usuarios&id=${u.id}&accion=toggle-estado`, {}).subscribe({
      next: () => { this.cargar(); this.exito = `Usuario ${u.estado === 'activo' ? 'desactivado' : 'activado'}.`; this.cdr.detectChanges(); },
      error: (err) => { this.error = err?.error?.error ?? 'No se pudo cambiar el estado.'; this.cdr.detectChanges(); }
    });
  }

  eliminar(u: Usuario): void {
    if (!confirm(`¿Eliminar permanentemente al usuario ${u.nombre} ${u.apellido}?`)) return;
    this.http.delete<any>(`${this.apiUrl}?recurso=usuarios&id=${u.id}`).subscribe({
      next: () => { this.cargar(); this.exito = 'Usuario eliminado.'; this.cdr.detectChanges(); },
      error: (err) => { this.error = err?.error?.error ?? 'No se pudo eliminar.'; this.cdr.detectChanges(); }
    });
  }

  get nombreRol(): string {
    return this.roles.find(r => r.id === this.form.rol_id)?.nombre ?? '';
  }
}

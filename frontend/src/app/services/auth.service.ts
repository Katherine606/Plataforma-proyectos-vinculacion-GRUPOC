import { Injectable } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { Observable, BehaviorSubject, tap } from 'rxjs';

export interface LoginCredentials {
  email: string;
  password: string;
}

export interface UsuarioSesion {
  id: number;
  nombre: string;
  apellido: string;
  correo: string;
  rol_id: number;
  rol: string;
}

export interface LoginResponse {
  token: string;
  usuario: UsuarioSesion;
}

@Injectable({
  providedIn: 'root'
})
export class AuthService {
  private readonly apiUrl = 'http://localhost:8000/index.php';
  private readonly tokenKey = 'token_vinculacion';
  private readonly userKey = 'usuario_vinculacion';

  private usuarioActual = new BehaviorSubject<UsuarioSesion | null>(null);
  public usuario$ = this.usuarioActual.asObservable();

  constructor(private http: HttpClient) {
    this.cargarSesion();
  }

  login(credentials: LoginCredentials): Observable<LoginResponse> {
    return this.http
      .post<LoginResponse>(`${this.apiUrl}?recurso=auth&accion=login`, credentials)
      .pipe(tap((response) => {
        this.setToken(response.token);
        this.guardarUsuario(response.usuario);
        this.usuarioActual.next(response.usuario);
      }));
  }

  logout(): void {
    localStorage.removeItem(this.tokenKey);
    sessionStorage.removeItem(this.userKey);
    this.usuarioActual.next(null);
  }

  isLoggedIn(): boolean {
    const token = this.getToken();
    if (!token) return false;

    const payload = this.decodePayload(token);
    if (!payload?.exp) return false;

    return Date.now() < payload.exp * 1000;
  }

  getUsuario(): UsuarioSesion | null {
    return this.usuarioActual.value;
  }

  getRole(): string | null {
    return this.usuarioActual.value?.rol ?? null;
  }

  getEmail(): string | null {
    return this.usuarioActual.value?.correo ?? null;
  }

  getName(): string | null {
    const u = this.usuarioActual.value;
    if (!u) return null;
    return (`${u.nombre} ${u.apellido}`.trim() || u.correo) ?? null;
  }

  getToken(): string | null {
    return localStorage.getItem(this.tokenKey);
  }

  private setToken(token: string): void {
    localStorage.setItem(this.tokenKey, token);
  }

  private guardarUsuario(usuario: UsuarioSesion): void {
    sessionStorage.setItem(this.userKey, JSON.stringify(usuario));
  }

  private cargarSesion(): void {
    const token = this.getToken();
    if (!token) return;

    const payload = this.decodePayload(token);
    if (!payload?.exp || Date.now() >= payload.exp * 1000) {
      this.logout();
      return;
    }

    const guardado = sessionStorage.getItem(this.userKey);
    if (guardado) {
      try {
        const usuario = JSON.parse(guardado);
        console.log('[Auth] Sesión restaurada:', usuario);
        this.usuarioActual.next(usuario);
        return;
      } catch {}
    }

    const usuario: UsuarioSesion = {
      id: payload.sub,
      nombre: payload.nombre ?? '',
      apellido: payload.apellido ?? '',
      correo: payload.correo ?? '',
      rol_id: payload.rol_id,
      rol: payload.rol ?? '',
    };
    console.log('[Auth] Sesión desde token:', usuario);
    this.guardarUsuario(usuario);
    this.usuarioActual.next(usuario);
  }

  private decodePayload(token: string | null): any | null {
    if (!token) return null;

    const partes = token.split('.');
    if (partes.length !== 3) return null;

    try {
      let base64 = partes[1].replace(/-/g, '+').replace(/_/g, '/');
      const padding = base64.length % 4;
      if (padding) base64 += '='.repeat(4 - padding);
      return JSON.parse(atob(base64));
    } catch {
      return null;
    }
  }
}

import { Injectable } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { Observable, tap } from 'rxjs';

export interface LoginCredentials {
  email: string;
  password: string;
}

export interface LoginResponse {
  token: string;
  usuario: {
    id: number;
    correo: string;
    rol_id: number;
    rol: 'Coordinador' | 'Tutor' | 'Estudiante' | string;
  };
}

@Injectable({
  providedIn: 'root'
})
export class AuthService {
  private readonly apiUrl = 'http://localhost:8000/index.php';
  private readonly tokenKey = 'token_vinculacion';

  constructor(private http: HttpClient) {}

  login(credentials: LoginCredentials): Observable<LoginResponse> {
    return this.http
      .post<LoginResponse>(`${this.apiUrl}?recurso=auth&accion=login`, credentials)
      .pipe(tap((response) => this.setToken(response.token)));
  }

  logout(): void {
    localStorage.removeItem(this.tokenKey);
  }

  isLoggedIn(): boolean {
    const token = this.getToken();
    if (!token) {
      return false;
    }

    const payload = this.decodePayload(token);
    if (!payload?.exp) {
      return false;
    }

    return Date.now() < payload.exp * 1000;
  }

  getRole(): string | null {
    const payload = this.decodePayload(this.getToken());
    return payload?.rol ?? null;
  }

  getEmail(): string | null {
    const payload = this.decodePayload(this.getToken());
    return payload?.correo ?? null;
  }

  getToken(): string | null {
    return localStorage.getItem(this.tokenKey);
  }

  private setToken(token: string): void {
    localStorage.setItem(this.tokenKey, token);
  }

  private decodePayload(token: string | null): any | null {
    if (!token) {
      return null;
    }

    const partes = token.split('.');
    if (partes.length !== 3) {
      return null;
    }

    try {
      let base64 = partes[1].replace(/-/g, '+').replace(/_/g, '/');
      const padding = base64.length % 4;
      if (padding) {
        base64 += '='.repeat(4 - padding);
      }

      const payload = JSON.parse(atob(base64));
      return payload;
    } catch {
      return null;
    }
  }
}

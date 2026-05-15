import { Injectable } from '@angular/core';
import { ActivatedRouteSnapshot, CanActivate, Router } from '@angular/router';
import { AuthService } from '../services/auth.service';

@Injectable({
  providedIn: 'root'
})
export class RoleGuard implements CanActivate {
  constructor(private authService: AuthService, private router: Router) {}

  canActivate(route: ActivatedRouteSnapshot): boolean {
    const rolesPermitidos = (route.data['roles'] as string[]) ?? [];
    const rolActual = this.authService.getRole();

    if (rolActual && rolesPermitidos.includes(rolActual)) {
      return true;
    }

    this.router.navigate(['/']);
    return false;
  }
}

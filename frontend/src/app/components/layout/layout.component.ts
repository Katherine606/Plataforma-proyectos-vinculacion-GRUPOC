import { Component, OnInit } from '@angular/core';
import { CommonModule } from '@angular/common';
import { Router, RouterOutlet, RouterLink, RouterLinkActive } from '@angular/router';
import { AuthService } from '../../services/auth.service';

/**
 * LAYOUT — Es el equivalente al header.php + footer.php
 * Contiene el sidebar y el área donde Angular dibuja cada vista (router-outlet)
 */
@Component({
  selector: 'app-layout',
  standalone: true,
  imports: [CommonModule, RouterOutlet, RouterLink, RouterLinkActive],
  templateUrl: './layout.component.html',
  styleUrl: './layout.component.css'
})
export class LayoutComponent implements OnInit {
  email = 'Usuario';
  rol = 'Sin rol';
  canManageProjects = false;
  canManageStudents = false;
  roleTitle = 'Consulta general';
  roleDescription = 'Vista de proyectos y seguimiento básico.';

  constructor(private authService: AuthService, private router: Router) {}

  ngOnInit(): void {
    this.email = this.authService.getEmail() ?? 'Usuario';
    this.rol = this.authService.getRole() ?? 'Sin rol';
    this.canManageProjects = this.rol === 'Coordinador';
    this.canManageStudents = this.rol === 'Tutor';

    if (this.rol === 'Coordinador') {
      this.roleTitle = 'Gestión de proyectos y cupos';
      this.roleDescription = 'Administra proyectos, cupos y asignación de tutores.';
    } else if (this.rol === 'Tutor') {
      this.roleTitle = 'Seguimiento de estudiantes';
      this.roleDescription = 'Revisa solicitudes y acompaña el avance de los estudiantes.';
    } else if (this.rol === 'Estudiante') {
      this.roleTitle = 'Consulta de proyectos';
      this.roleDescription = 'Explora proyectos disponibles y revisa su estado.';
    }
  }

  logout(): void {
    this.authService.logout();
    this.router.navigate(['/login']);
  }
}

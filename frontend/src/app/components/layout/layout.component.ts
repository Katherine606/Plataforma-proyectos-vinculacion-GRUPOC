import { Component, OnInit, OnDestroy } from '@angular/core';
import { CommonModule } from '@angular/common';
import { Router, RouterOutlet, RouterLink, RouterLinkActive } from '@angular/router';
import { Subscription } from 'rxjs';
import { AuthService, UsuarioSesion } from '../../services/auth.service';

@Component({
  selector: 'app-layout',
  standalone: true,
  imports: [CommonModule, RouterOutlet, RouterLink, RouterLinkActive],
  templateUrl: './layout.component.html',
  styleUrl: './layout.component.css'
})
export class LayoutComponent implements OnInit, OnDestroy {
  email = 'Usuario';
  rol = 'Sin rol';
  canManageProjects = false;
  canManageStudents = false;
  isEstudiante = false;
  roleTitle = 'Consulta general';
  roleDescription = 'Vista de proyectos y seguimiento básico.';

  private sub?: Subscription;

  constructor(private authService: AuthService, private router: Router) {}

  ngOnInit(): void {
    this.sub = this.authService.usuario$.subscribe(u => {
      if (u) {
        this.email = (`${u.nombre} ${u.apellido}`.trim() || u.correo);
        this.rol = u.rol;
      } else {
        this.email = 'Usuario';
        this.rol = 'Sin rol';
      }

      const rolNormalizado = this.rol.toLowerCase();

      this.canManageProjects = rolNormalizado.includes('coordinador');
      this.canManageStudents = rolNormalizado.includes('tutor');
      this.isEstudiante = rolNormalizado.includes('estudiante');

      console.log('[Layout] rol:', this.rol, '| rolNormalizado:', rolNormalizado,
        '| canManageStudents:', this.canManageStudents);

      if (rolNormalizado.includes('coordinador')) {
        this.roleTitle = 'Gestión de proyectos y cupos';
        this.roleDescription = 'Administra proyectos, cupos y asignación de tutores.';
      } else if (rolNormalizado.includes('tutor')) {
        this.roleTitle = 'Seguimiento de estudiantes';
        this.roleDescription = 'Revisa solicitudes y acompaña el avance de los estudiantes.';
      } else if (rolNormalizado.includes('estudiante')) {
        this.roleTitle = 'Consulta de proyectos';
        this.roleDescription = 'Explora proyectos disponibles y revisa su estado.';
      }
    });
  }

  ngOnDestroy(): void {
    this.sub?.unsubscribe();
  }

  logout(): void {
    this.authService.logout();
    this.router.navigate(['/login']);
  }
}

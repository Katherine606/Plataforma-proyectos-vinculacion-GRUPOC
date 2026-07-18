import { Routes } from '@angular/router';
import { LayoutComponent } from './components/layout/layout.component';
import { ListaProyectosComponent } from './components/lista-proyectos/lista-proyectos.component';
import { FormularioProyectoComponent } from './components/formulario-proyecto/formulario-proyecto.component';
import { EditorProyectosComponent } from './components/editor-proyectos/editor-proyectos.component';
import { SolicitudesComponent } from './components/solicitudes/solicitudes.component';
import { LoginComponent } from './components/login/login.component';
import { RegistroHorasComponent } from './components/registro-horas/registro-horas.component';
import { MisHorasComponent } from './components/mis-horas/mis-horas.component';
import { AprobacionHorasComponent } from './components/aprobacion-horas/aprobacion-horas.component';
import { CrearActividadComponent } from './components/crear-actividad/crear-actividad.component';
import { DashboardComponent } from './components/dashboard/dashboard.component';
import { AuthGuard } from './guards/auth.guard';
import { RoleGuard } from './guards/role.guard';

export const routes: Routes = [
  { path: 'login', component: LoginComponent },
  // Todas las rutas viven dentro del Layout (sidebar + contenido)
  {
    path: '',
    component: LayoutComponent,
    canActivate: [AuthGuard],
    children: [
      { path: '', component: DashboardComponent },
      {
        path: 'proyectos',
        component: ListaProyectosComponent
      },
      {
        path: 'nuevo',
        component: FormularioProyectoComponent,
        canActivate: [RoleGuard],
        data: { roles: ['Coordinador'] }
      },
      {
        path: 'editor',
        component: EditorProyectosComponent,
        canActivate: [RoleGuard],
        data: { roles: ['Coordinador'] }
      },
      {
        path: 'solicitudes',
        component: SolicitudesComponent,
        canActivate: [RoleGuard],
        data: { roles: ['Tutor', 'Coordinador'] }
      },
      {
        path: 'registro-horas',
        component: RegistroHorasComponent,
        canActivate: [RoleGuard],
        data: { roles: ['Estudiante'] }
      },
      {
        path: 'mis-horas',
        component: MisHorasComponent,
        canActivate: [RoleGuard],
        data: { roles: ['Estudiante'] }
      },
      {
        path: 'aprobacion-horas',
        component: AprobacionHorasComponent,
        canActivate: [RoleGuard],
        data: { roles: ['Tutor'] }
      },
      {
        path: 'crear-actividad',
        component: CrearActividadComponent,
        canActivate: [RoleGuard],
        data: { roles: ['Tutor'] }
      }
    ]
  },
  { path: '**', redirectTo: '' }
];

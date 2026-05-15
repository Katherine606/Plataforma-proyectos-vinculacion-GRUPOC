import { Routes } from '@angular/router';
import { LayoutComponent } from './components/layout/layout.component';
import { ListaProyectosComponent } from './components/lista-proyectos/lista-proyectos.component';
import { FormularioProyectoComponent } from './components/formulario-proyecto/formulario-proyecto.component';
import { EditorProyectosComponent } from './components/editor-proyectos/editor-proyectos.component';
import { SolicitudesComponent } from './components/solicitudes/solicitudes.component';
import { LoginComponent } from './components/login/login.component';
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
      { path: '',             component: ListaProyectosComponent },
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
        data: { roles: ['Tutor'] }
      }
    ]
  },
  { path: '**', redirectTo: '' }
];

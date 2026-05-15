import { ApplicationConfig, provideZonelessChangeDetection } from '@angular/core';
import { provideRouter } from '@angular/router';
import { provideHttpClient, withInterceptors } from '@angular/common/http';

import { routes } from './app.routes';
import { authInterceptor } from './interceptors/auth.interceptor';

export const appConfig: ApplicationConfig = {
  providers: [
    // Angular 20+ usa detección de cambios sin Zone.js
    provideZonelessChangeDetection(),
    provideRouter(routes),
    // Habilita HttpClient en toda la app (necesario para hablar con PHP)
    provideHttpClient(withInterceptors([authInterceptor]))
  ]
};

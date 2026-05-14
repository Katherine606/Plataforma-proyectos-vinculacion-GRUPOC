# Plataforma de Gestión de Proyectos de Vinculación

## Descripción General

La presente guía tiene como objetivo detallar el procedimiento necesario para descargar, configurar y ejecutar el proyecto **“Plataforma de Gestión de Proyectos de Vinculación”**.

El sistema fue desarrollado utilizando una arquitectura **MVC (Modelo Vista Controlador)**, implementando:

- Angular para el frontend
- PHP para el backend
- MySQL como gestor de base de datos

# Requisitos Previos

Antes de ejecutar el sistema es necesario instalar las siguientes herramientas:

- Git
- Node.js
- Angular CLI
- XAMPP
- Visual Studio Code


# 1. Descargar el Proyecto desde GitHub

Abrir CMD o PowerShell y ejecutar:

```bash
git clone https://github.com/Katherine606/Plataforma-proyectos-vinculacion-GRUPOC.git
```

Este comando descargará automáticamente una carpeta llamada: Plataforma-proyectos-vinculacion-GRUPOC

Ingresar a la carpeta del proyecto:
```bash
cd Plataforma-proyectos-vinculacion-GRUPOC
```
# 2. Abrir el Proyecto en Visual Studio Code y abrir la terminal
Abrir Visual Studio Code.

Buscar y abrir la carpeta: Plataforma-proyectos-vinculacion-GRUPOC

Verificar que existan las siguientes carpetas:
frontend
backend
database
docs

Dentro de Visual Studio Code abrir una nueva terminal

+ Terminal → New Terminal

La terminal aparecerá en la parte inferior del IDE.

# 3. Configuración Inicial de PowerShell (Solo la Primera Vez)

En algunos equipos Windows, PowerShell bloquea la ejecución de scripts de Node.js.

Si ocurre este problema ejecutar:
```bash
Set-ExecutionPolicy RemoteSigned -Scope CurrentUser
```

Luego escribir:
```bash
Y
```

y presionar Enter.

Cerrar y volver a abrir Visual Studio Code.

# 4. Instalación y Ejecución del Frontend

En la terminal ejecutar:
```bash
cd frontend
```

Instalar dependencias del proyecto:
```bash
npm install
```

Instalar Angular CLI globalmente:
```bash
npm install -g @angular/cli
```

Verificar instalación:
```bash
ng version
```

Ejecutar el servidor Angular:
```bash
ng serve
```

Abrir en el navegador: http://localhost:4200

# 5. Ejecución del Backend

Abrir una nueva terminal dentro de Visual Studio Code.

Regresar a la carpeta principal:
```bash
cd ..
```

Ingresar a la carpeta backend:
```bash
cd backend
```

Ejecutar el servidor PHP:

```bash
C:\xampp\php\php.exe -S localhost:8000
```

Si la ejecución es correcta aparecerá: PHP Development Server started

# 7. Verificación del Backend

Abrir en el navegador:

http://localhost:8000/index.php?recurso=proyectos

Si el backend funciona correctamente se mostrará información JSON correspondiente a los proyectos registrados.

# 8. Base de Datos

Actualmente el proyecto se encuentra en fase de diseño y estructuración de base de datos.

Las tablas planteadas inicialmente son:

roles, usuarios, facultades, carreras, proyectos, solicitudes y horas_vinculacion

La implementación física en MySQL será desarrollada en fases posteriores del proyecto.

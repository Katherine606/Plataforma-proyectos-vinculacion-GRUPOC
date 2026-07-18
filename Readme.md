# Plataforma de Gestión de Proyectos de Vinculación

## 1. DESCRIPCIÓN GENERAL

La presente guía tiene como objetivo detallar el procedimiento necesario para descargar, configurar y ejecutar el proyecto **“Plataforma de Gestión de Proyectos de Vinculación”**.

El sistema fue desarrollado utilizando una arquitectura **MVC (Modelo Vista Controlador)**, implementando:

- Angular para el frontend
- PHP para el backend
- MySQL como gestor de base de datos

## 2. REQUISITOS PREVIOS

Antes de ejecutar el sistema es necesario instalar las siguientes herramientas:

  - Git (https://git-scm.com/)
  - Node.js v18+ (https://nodejs.org/)
  - Angular CLI (se instala en el paso 4)
  - XAMPP (https://www.apachefriends.org/) - incluye MySQL y PHP
  - Visual Studio Code (https://code.visualstudio.com/)

Verificar las instalaciones abriendo una terminal y ejecutando:

  git --version
  node --version
  npm --version


## 3. DESCARGAR EL PROYECTO DESDE GITHUB

Abrir CMD o PowerShell y ejecutar:

```bash
git clone https://github.com/Katherine606/Plataforma-proyectos-vinculacion-GRUPOC.git
```

Este comando descargará automáticamente una carpeta llamada: Plataforma-proyectos-vinculacion-GRUPOC

Ingresar a la carpeta del proyecto:
```bash
cd Plataforma-proyectos-vinculacion-GRUPOC
```
## 4. ABRIR EL PROYECTO EN VISUAL STUDIO CODE

  - Abrir Visual Studio Code.
  - Buscar y abrir la carpeta: Plataforma-proyectos-vinculacion-GRUPOC
  - Verificar que existan las siguientes carpetas: frontend, backend, database, docs
  - Dentro de Visual Studio Code abrir una nueva terminal:
    Terminal > New Terminal
  - La terminal aparecera en la parte inferior del IDE.

## 5. CONFIGURACION INICIAL DE POWERSHELL (Solo la Primera Vez)

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

## 6. Instalación y Ejecución del Frontend

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

## 6. CONFIGURACION DE LA BASE DE DATOS (OBLIGATORIO)

### 6.1 Iniciar Apache y MySQL en XAMPP

  - Abrir el Panel de Control de XAMPP.
  - Iniciar el servicio Apache.
  - Iniciar el servicio MySQL.

### 6.2 Crear la base de datos

  Abrir phpMyAdmin en el navegador: http://localhost/phpmyadmin

  Opcion A - Importar desde el archivo SQL completo (recomendado):

  - Pestaña "Importar" > "Seleccionar archivo"
  - Seleccionar el archivo: base/vinculacion_db
  - Hacer clic en "Continuar" / "Go"

  Opcion B - Crear manualmente y ejecutar migraciones:

  Paso 1: Crear la base de datos
    En la pestaña "SQL" ejecutar:
      CREATE DATABASE vinculacion_db
      CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

  Paso 2: Importar el esquema principal
    Pestaña "Importar" > seleccionar: database/schema.sql > Ejecutar

  Paso 3: Ejecutar las migraciones en orden
    Importar la base: "vinculacion_db"

### 6.3 Credenciales de base de datos

  El backend se conecta automaticamente con estos parametros (config/database.php):
    Servidor:  localhost
    Puerto:    3306
    Usuario:   root
    Contrasena: (vacia)
    Base:      vinculacion_db

  NOTA: Estas son las credenciales por defecto de XAMPP. Si cambiaste la
  contrasena de root, edita el archivo backend/config/database.php.


## 7. EJECUTAR EL BACKEND

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

## 8. VERIFICACION DEL BACKEND

Abrir en el navegador:

http://localhost:8000/index.php?recurso=proyectos

Si el backend funciona correctamente se mostrará información JSON correspondiente a los proyectos registrados.

## 9. CREDENCIALES DE PRUEBA

  Usuario:               Contrasena:    Rol:
  coordinador@ug.edu.ec  Coord123*      Coordinador
  tutor@ug.edu.ec        Tutor123*      Tutor Academico
  estudiante@ug.edu.ec   Est12345*      Estudiante



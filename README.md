# SFS Access — aplicación local para Windows

Aplicación de escritorio en Python/PySide6 para administrar estudiantes, personal,
huellas y asistencia. Utiliza MySQL/MariaDB y el lector **SecuGen Hamster Pro**;
la captura biométrica se ejecuta en un agente .NET con SourceAFIS.

## Requisitos

- Windows.
- Python 3.10 o superior.
- .NET 10 SDK para compilar el agente.
- MySQL/MariaDB con la base `sfs_access_control`.
- Controladores y SDK de SecuGen instalados para usar el lector.

## Primera instalación

Desde PowerShell, en la carpeta de este proyecto:

```powershell
py -m venv .venv
.\.venv\Scripts\python.exe -m pip install -r requirements-desktop.txt
dotnet build "SFS Access.csproj"
```

Después, abre **Iniciar SFS Access.bat** con doble clic. El lanzador requiere el
entorno `.venv`; no es necesario iniciar el agente biométrico manualmente.

## Base de datos

Inicia MySQL/MariaDB antes de abrir la aplicación. Por defecto, la app intenta
conectarse a `localhost:3306`, base `sfs_access_control`, usuario `root` y
contraseña vacía. Configura estas variables de entorno si tu conexión es distinta:

- `SFS_DB_HOST`
- `SFS_DB_PORT`
- `SFS_DB_NAME`
- `SFS_DB_USER`
- `SFS_DB_PASSWORD`

La aplicación usa las tablas existentes y no las crea ni modifica al iniciar.
Para una instalación nueva, consulta los scripts SQL de `database/`. Ejecuta las
migraciones solo si tu base aún no tiene los cambios correspondientes:

- `migrate_single_student_fingerprint.sql`: deja una huella por estudiante.
- `migrate_fingerprint_template_capacity.sql`: amplía el espacio de las plantillas.
- `migrate_sourceafis_templates.sql`: prepara plantillas SourceAFIS cuando aplique.

## Lector SecuGen

Conecta el Hamster Pro y confirma que el controlador del fabricante esté instalado.
El agente usa las DLL x86 del SDK SecuGen y el proyecto .NET las configura mediante
`SFS Access.csproj`. La aplicación permite enrolar huellas y registrar asistencia
continua desde la pestaña **Asistencia**; una huella desconocida no detiene el
lector. La opción **Apagar huellero** detiene el escaneo.

## Estructura activa

- `main.py`, `sfs_desktop/`: interfaz y lógica de la aplicación Python.
- `requirements-desktop.txt`: dependencias Python.
- `SFS Access.csproj`, `Program.cs`, `Biometrics/`: agente del lector.
- `database/`: scripts de esquema, datos y migraciones SQL.
- `assets/img/logo.png` y `assets/img/logo-transparent.png`: logos usados por la app.
- `api/`: archivos de comunicación temporal entre la app y el agente biométrico.
- `tests/`: pruebas automatizadas.

El antiguo sitio PHP y sus recursos se conservaron, sin borrarlos, dentro de
`Archivo/sitio_web/`. Diagramas y material institucional archivados están en sus
respectivas carpetas bajo `Archivo/`; no son necesarios para ejecutar la app Python.

# 🏫 SFS ACCESS CONTROL - I.E. JORGE ROBLEDO

Sistema web de control de acceso estudiantil y gestión de asistencia a clases en tiempo real para la **I.E. Jorge Robledo**. Diseñado para reemplazar los registros manuales en cuadernos mediante la integración con lectores biométricos de huella dactilar y consolidación automática de asistencia por grado y aula.

---

## 🚀 Arquitectura Técnica

- **Lenguaje:** PHP 8.x Nativo (Sin frameworks externos).
- **Patrón de Diseño:** Modelo - Vista - Controlador (MVC).
- **Base de Datos:** MySQL / MariaDB (Motor `InnoDB`, Codificación `utf8mb4`).
- **Capa de Datos:** PDO (PHP Data Objects) con Sentencias Preparadas contra Inyección SQL.
- **Frontend:** HTML5, CSS3, Bootstrap 5, FontAwesome 6, JavaScript Vanilla (AJAX Fetch API).
- **Integración Hardware:** Endpoint API REST (JSON) compatible con lectores biométricos (Arduino, ESP32, Python, etc.).

---

## 📁 Estructura del Proyecto

```text
SFS Access/
│
├── api/
│   ├── registrar_huella.php        # Endpoint REST para sensores y scripts
│   └── simular_sensor.py           # Script Python para pruebas de integración hardware
│
├── assets/
│   ├── css/
│   │   └── styles.css              # Hoja de estilos institucionales y componentes UI
│   └── img/                        # Logotipos, íconos SVG e imágenes de interfaz
│
├── config/
│   ├── auth.php                    # Middleware de control de sesiones y autenticación
│   └── database.php                # Conexión Singleton PDO a MySQL
│
├── controllers/
│   ├── AccesoController.php        # Lógica de validación biométrica y control de acceso
│   ├── AuthController.php          # Lógica de autenticación, login y registro
│   └── ReporteController.php       # Consolidación de datos y reportes de asistencia escolar
│
├── database/
│   ├── sfs_access_control.sql      # Script DDL/DML principal para phpMyAdmin
│   ├── datos_prueba.sql            # Script DML de datos de prueba institucionales
│   ├── diagrama_relacional_bd.drawio # Diagrama Relacional de la BD
│   └── modelo_entidad_relacion.drawio# Modelo Entidad-Relación (MER)
│
├── docs/
│   ├── diagramas/                  # Diagramas de arquitectura y modelado
│   └── mockups/                    # Prototipos y maquetas HTML originales
│
├── models/
│   ├── AccesoModel.php             # Registros de entrada/salida y métricas de aforo
│   └── UsuarioModel.php            # Consultas de usuarios, roles y huellas
│
├── views/
│   └── dashboard.php               # Vista interactiva con KPIs en vivo y simulador
│
├── index.php                       # Front Controller y Portal de Inicio
├── login.php                       # Inicio de sesión con autenticación PHP y sesiones
├── registro.php                    # Registro de usuarios y estudiantes
├── nosotros.php                    # Información del equipo y proyecto
├── blog.php                        # Artículos de tecnología biométrica
├── perfil.php                      # Perfil de usuario dinámico y auditoría
├── dashboard.php                   # Acceso directo al Panel de Control en Vivo
├── logout.php                      # Cierre de sesión seguro
└── README.md                       # Documentación técnica
```

---

## 🛠️ Guía de Instalación en XAMPP

1. **Ubicación del Proyecto:**
   - Coloca la carpeta `SFS Access` dentro del directorio `htdocs` de XAMPP:
     `C:\xampp\htdocs\SFS Access\`

2. **Iniciar Servicios:**
   - Abre el **XAMPP Control Panel** e inicia los módulos **Apache** y **MySQL**.

3. **Importar Base de Datos:**
   - Ingresa a [http://localhost/phpmyadmin](http://localhost/phpmyadmin).
   - Ve a la pestaña **Importar** y selecciona el archivo [`database/sfs_access_control.sql`](file:///c:/xampp/htdocs/SFS%20Access/database/sfs_access_control.sql).
   - Haz clic en **Continuar**. Se creará la base de datos `sfs_access_control` con sus tablas y datos de prueba.

4. **Abrir el Sistema:**
   - Abre tu navegador web e ingresa a:
     [http://localhost/SFS%20Access/](http://localhost/SFS%20Access/)

---

## 🧪 Casos de Prueba Incluidos

El script SQL incluye usuarios precargados para verificar todas las reglas de negocio:

| Documento | Nombre | Grado | Rol | Estado | Huella Template | Resultado Esperado |
| :--- | :--- | :--- | :--- | :--- | :--- | :--- |
| `10359001` | Alejandra Martínez | 11°A | ESTUDIANTE | **ACTIVO** | `HUELLA_HEX_SAMPLE_001` | ✅ **APROBADO** (Registra Entrada / Salida) |
| `10359002` | Carlos Rodríguez | 10°B | ESTUDIANTE | **ACTIVO** | `HUELLA_HEX_SAMPLE_002` | ✅ **APROBADO** (Registra Asistencia a Clases) |
| `10359003` | Lucía Gómez | 11°A | ESTUDIANTE | **INACTIVO** | `HUELLA_HEX_SAMPLE_003` | 🚫 **RECHAZADO** (Permiso denegado) |
| `99999999` | No Registrado | N/A | N/A | N/A | `HUELLA_DESCONOCIDA` | 🚫 **RECHAZADO** (Persona ajena) |

---

## 📡 API REST para Sensores Biométricos

- **URL:** `http://localhost/SFS%20Access/api/registrar_huella.php`
- **Método:** `POST`
- **Content-Type:** `application/json`

### Ejemplo de Payload (Entrada):
```json
{
  "huella": "HUELLA_HEX_SAMPLE_001",
  "tipo_evento": "ENTRADA"
}
```

### Ejemplo de Respuesta Exitosa (HTTP 200):
```json
{
  "status": "exito",
  "codigo": "ACCESO_CONCEDIDO",
  "mensaje": "¡Bienvenido(a)! ENTRADA registrada correctamente.",
  "acceso_permitido": true,
  "tipo_evento": "ENTRADA",
  "usuario": {
    "id": 2,
    "nombre": "Alejandra Martínez",
    "documento": "10359001",
    "grado": "11°A",
    "rol": "ESTUDIANTE"
  },
  "registro_id": 5,
  "fecha_hora": "2026-08-21 15:50:00"
}
```

---

## 📚 Módulo de Control de Asistencia a Clases

El sistema calcula en tiempo real:
1. **Total de Estudiantes Presentes:** Conteo de estudiantes cuyo último registro hoy fue `ENTRADA` con estado `APROBADO`.
2. **Desglose de Asistencia por Grado:** Cantidad de alumnos presentes por salón (ej. 10°A, 11°B) para control docente y académico.
3. **Descarga en CSV:** Exportación directa para auditoría y planillas de asistencia escolar con un solo clic.

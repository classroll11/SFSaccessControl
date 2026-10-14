# Archivo de recursos no requeridos por la aplicación Python

El contenido de esta carpeta se conservó para consulta o posible reutilización.
El sitio web PHP y sus componentes ya no están en la raíz activa del proyecto.
El archivo `.htaccess` de esta carpeta bloquea el acceso HTTP directo al archivo
archivado, que puede incluir configuración o documentos institucionales.

- `sitio_web/`: copia ordenada por rutas originales de controladores, vistas,
  configuración y recursos exclusivos del antiguo sitio PHP. También incluye el
  README anterior y componentes web de biometría.
- `documentacion_bd/`: diagramas de la base de datos que no necesita la app al
  ejecutarse. Los scripts `.sql` permanecen en `../database/`.
- `material_institucional/`: documentos de apoyo no usados por la app.

Los archivos de `../api/` que se dejaron fuera del archivo son el espacio de
comunicación del agente .NET y no deben moverse: allí se escriben estados,
solicitudes y señales de apagado del lector.

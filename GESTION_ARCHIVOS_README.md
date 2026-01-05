# Módulo de Gestión de Archivos

Este módulo permite gestionar archivos asociados a diferentes tipos de reportes.

## Características

- ✅ Selección de tipos de reportes existentes
- ✅ Subida de múltiples archivos por tipo de reporte
- ✅ Soporte para cualquier formato de archivo (DOCX, VSDX, PDF, XLSB, etc.)
- ✅ Almacenamiento optimizado (solo rutas en BD, archivos en storage)
- ✅ Visualización de archivos por tipo de reporte
- ✅ Descarga de archivos
- ✅ Eliminación de archivos (soft delete)
- ✅ Auditoría completa (quién creó, actualizó, eliminó)
- ✅ Relaciones Eloquent ORM

## Comandos para Deploy en Ubuntu con PHP 8.2

### 1. Ejecutar la migración

```bash
php artisan migrate
```

Este comando creará la tabla `report_type_files` con la siguiente estructura:
- `id`: ID único del archivo
- `report_type_id`: Relación con el tipo de reporte
- `nombre_original`: Nombre original del archivo
- `nombre_archivo`: Nombre único generado (UUID)
- `ruta`: Ruta completa en storage
- `extension`: Extensión del archivo
- `tamano`: Tamaño en bytes
- `created_by`, `updated_by`, `deleted_by`: Auditoría
- `created_at`, `updated_at`, `deleted_at`: Timestamps

### 2. Configurar permisos de storage (Ubuntu)

```bash
# Asegurarse de que el directorio storage tenga los permisos correctos
sudo chown -R www-data:www-data /ruta/a/reportesIA/storage
sudo chmod -R 775 /ruta/a/reportesIA/storage

# Específicamente para el directorio de archivos
sudo mkdir -p /ruta/a/reportesIA/storage/app/report_files
sudo chown -R www-data:www-data /ruta/a/reportesIA/storage/app/report_files
sudo chmod -R 775 /ruta/a/reportesIA/storage/app/report_files
```

### 3. Configuración de límites de subida (opcional)

Editar `/etc/php/8.2/apache2/php.ini` o `/etc/php/8.2/fpm/php.ini`:

```ini
upload_max_filesize = 50M
post_max_size = 50M
max_execution_time = 300
```

Reiniciar el servidor web:

```bash
sudo systemctl restart apache2
# O si usas PHP-FPM:
sudo systemctl restart php8.2-fpm
```

## Estructura del Módulo

### Base de Datos

**Tabla:** `report_type_files`
- Relación: Muchos archivos pueden pertenecer a un tipo de reporte
- Foreign Keys: `report_type_id`, `created_by`, `updated_by`, `deleted_by`
- Soft Deletes: Sí

### Modelos Eloquent

**ReportTypeFile** (`app/Models/ReportTypeFile.php`)
- Relaciones:
  - `reportType()`: Pertenece a un ReportType
  - `creator()`, `updater()`, `deleter()`: Relaciones con User
- Atributos calculados:
  - `tamano_formateado`: Formatea el tamaño en KB, MB, GB

**ReportType** (`app/Models/ReportType.php`)
- Nueva relación añadida:
  - `files()`: Tiene muchos ReportTypeFile

### Controlador

**ReportTypeFileController** (`app/Http/Controllers/ReportTypeFileController.php`)

Métodos:
- `index()`: Lista todos los tipos de reportes con contador de archivos
- `show(ReportType $reportType)`: Muestra archivos de un tipo específico
- `create(ReportType $reportType)`: Formulario para subir archivos
- `store(Request $request, ReportType $reportType)`: Procesa la subida
- `download(ReportTypeFile $file)`: Descarga un archivo
- `destroy(ReportTypeFile $file)`: Elimina un archivo (soft delete)

### Rutas

Prefijo: `/admin/report-files`
Middleware: `auth`, `admin`

```
GET     /admin/report-files                          - Lista tipos de reportes
GET     /admin/report-files/{reportType}             - Ver archivos del tipo
GET     /admin/report-files/{reportType}/create      - Formulario de subida
POST    /admin/report-files/{reportType}             - Subir archivos
GET     /admin/report-files/file/{file}/download     - Descargar archivo
DELETE  /admin/report-files/file/{file}              - Eliminar archivo
```

### Vistas

**index.blade.php**: Lista de tipos de reportes con contador de archivos
**show.blade.php**: Listado detallado de archivos por tipo con opciones de descarga/eliminar
**create.blade.php**: Formulario de subida múltiple con drag & drop

### Menú

Nueva opción agregada al sidebar en la sección ADMIN:
- **Gestión de Archivos** - Visible solo para administradores

## Uso

### Para subir archivos:

1. Ir a "Gestión de Archivos" en el menú
2. Seleccionar el tipo de reporte deseado
3. Click en "Subir Archivo" (aparecerá confirmación con el nombre del tipo)
4. **VERIFICAR** el banner azul prominente que muestra el tipo de reporte seleccionado
5. Seleccionar uno o varios archivos (máx 50MB cada uno)
6. Click en "Subir Archivos"

### Para ver archivos:

1. Ir a "Gestión de Archivos"
2. Click en "Ver Archivos" del tipo deseado
3. **VERIFICAR** el banner azul prominente que muestra el tipo de reporte seleccionado
4. Se muestra la lista con:
   - Icono según tipo de archivo
   - Nombre original
   - Extensión
   - Tamaño formateado
   - Quién lo subió
   - Fecha de subida
   - Acciones: Descargar / Eliminar

### Indicadores visuales para prevenir errores:

#### En la lista principal:
- **Alerta informativa azul**: Recuerda verificar el tipo de reporte correcto
- **Hover azul**: Al pasar el cursor sobre una fila, se resalta con fondo azul
- **Confirmación al subir**: Popup que muestra el nombre e ID del tipo antes de continuar
- **Badge azul**: Tipo de reporte con archivos (muestra cantidad)
- **Badge gris**: Tipo de reporte sin archivos
- **Icono destacado**: Cada tipo tiene su icono con efecto hover

#### En la vista de archivos y subida:
- **Banner azul sticky**: Muestra prominentemente el tipo de reporte seleccionado
  - Posición sticky (siempre visible al hacer scroll)
  - Gradiente azul llamativo
  - Muestra: "Tipo de Reporte Seleccionado" o "Subirás archivos a:"
  - Incluye nombre del tipo e ID
  - En vista de archivos: también muestra cantidad de archivos
- **Iconos diferenciados**: Por tipo de archivo (PDF rojo, Word azul, Excel verde, etc.)

## Seguridad

- ✅ Solo administradores pueden acceder al módulo
- ✅ Validación de archivos en servidor
- ✅ Nombres de archivo generados con UUID (evita colisiones)
- ✅ Archivos almacenados fuera del directorio público
- ✅ Soft delete con auditoría
- ✅ Límite de 50MB por archivo

## Look and Feel

El módulo conserva completamente el look and feel del sistema:
- ✅ Mismo esquema de colores (hando-*)
- ✅ Cards con diseño consistente
- ✅ Tablas con hover states
- ✅ Botones con variantes primary/secondary
- ✅ Mensajes de éxito/error uniformes
- ✅ Dark mode compatible
- ✅ Iconos SVG consistentes
- ✅ Transiciones suaves

## Notas Técnicas

- Los archivos se almacenan en `storage/app/report_files/{report_type_id}/`
- Cada archivo tiene un nombre único (UUID) para evitar conflictos
- La base de datos solo almacena las rutas, no los archivos binarios
- El sistema es compatible con PHP 8.2
- No hay límite en los tipos de archivo soportados

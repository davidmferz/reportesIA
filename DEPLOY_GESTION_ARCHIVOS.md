# Deploy del Módulo de Gestión de Archivos

## ⚠️ ERROR ACTUAL
```
Route [admin.report-files.index] not defined.
```

Este error ocurre porque el servidor no tiene los archivos nuevos del módulo de gestión de archivos.

---

## 📋 CHECKLIST DE DEPLOY

### 1️⃣ Subir Archivos al Servidor

Debes subir los siguientes archivos desde tu local al servidor de producción:

#### A) Controlador
```bash
app/Http/Controllers/ReportTypeFileController.php
```

#### B) Modelo
```bash
app/Models/ReportTypeFile.php
```

#### C) Modelo Actualizado
```bash
app/Models/ReportType.php
```

#### D) Migración
```bash
database/migrations/2026_01_05_165639_create_report_type_files_table.php
```

#### E) Rutas (CRÍTICO)
```bash
routes/web.php
```

#### F) Vistas (3 archivos)
```bash
resources/views/admin/report-files/index.blade.php
resources/views/admin/report-files/show.blade.php
resources/views/admin/report-files/create.blade.php
```

#### G) Componente Sidebar Actualizado
```bash
resources/views/components/crm/sidebar.blade.php
```

---

### 2️⃣ Conectarse al Servidor

```bash
ssh usuario@mfg.blmovil.com
```

O usar SFTP/FTP para subir los archivos.

---

### 3️⃣ Comandos en el Servidor (EN ORDEN)

Una vez que hayas subido todos los archivos, ejecuta estos comandos en el servidor:

#### A) Ir al directorio del proyecto
```bash
cd /ruta/a/tu/proyecto/reportesIA
```

#### B) Limpiar TODOS los caches de Laravel
```bash
# Limpiar cache de rutas (MUY IMPORTANTE)
php artisan route:clear

# Limpiar cache de configuración
php artisan config:clear

# Limpiar cache de vistas
php artisan view:clear

# Limpiar cache general
php artisan cache:clear

# Limpiar cache de Composer autoload
composer dump-autoload
```

#### C) Ejecutar la migración
```bash
php artisan migrate
```

Esto creará la tabla `report_type_files` en la base de datos.

#### D) Verificar que las rutas estén registradas
```bash
php artisan route:list | grep report-files
```

Deberías ver algo como:
```
GET|HEAD   admin/report-files ..................... admin.report-files.index
GET|HEAD   admin/report-files/{reportType} ......... admin.report-files.show
GET|HEAD   admin/report-files/{reportType}/create .. admin.report-files.create
POST       admin/report-files/{reportType} ......... admin.report-files.store
GET|HEAD   admin/report-files/file/{file}/download . admin.report-files.download
DELETE     admin/report-files/file/{file} .......... admin.report-files.destroy
```

#### E) Configurar permisos del storage
```bash
# Crear directorio para archivos
sudo mkdir -p storage/app/report_files

# Asignar permisos
sudo chown -R www-data:www-data storage
sudo chmod -R 775 storage

# Específicamente para report_files
sudo chown -R www-data:www-data storage/app/report_files
sudo chmod -R 775 storage/app/report_files
```

#### F) Reiniciar servicios (si es necesario)
```bash
# Si usas Apache
sudo systemctl restart apache2

# Si usas PHP-FPM
sudo systemctl restart php8.2-fpm

# Si usas Nginx
sudo systemctl restart nginx
```

---

## 🔍 VERIFICACIÓN

### 1. Verificar que los archivos existen en el servidor

```bash
# Verificar controlador
ls -la app/Http/Controllers/ReportTypeFileController.php

# Verificar modelo
ls -la app/Models/ReportTypeFile.php

# Verificar vistas
ls -la resources/views/admin/report-files/

# Verificar rutas
cat routes/web.php | grep report-files
```

### 2. Verificar que la tabla se creó

```bash
php artisan tinker
```

Luego dentro de tinker:
```php
\Schema::hasTable('report_type_files');
// Debe devolver: true

\App\Models\ReportTypeFile::count();
// Debe devolver: 0 (si no hay archivos aún)
```

### 3. Probar la ruta en el navegador

Accede a:
```
https://mfg.blmovil.com/admin/report-files
```

Deberías ver la lista de tipos de reportes.

---

## 🚨 SOLUCIÓN RÁPIDA (Si tienes acceso SSH)

Si tienes acceso SSH al servidor, ejecuta este script todo junto:

```bash
# Ir al directorio del proyecto
cd /var/www/html/reportesIA  # Ajusta la ruta según tu servidor

# Limpiar todos los caches
php artisan route:clear
php artisan config:clear
php artisan view:clear
php artisan cache:clear
composer dump-autoload

# Ejecutar migración
php artisan migrate

# Configurar permisos
sudo mkdir -p storage/app/report_files
sudo chown -R www-data:www-data storage
sudo chmod -R 775 storage

# Verificar rutas
php artisan route:list | grep report-files

# Reiniciar Apache (si usas Apache)
sudo systemctl restart apache2
```

---

## 📦 MÉTODO ALTERNATIVO: Git

Si usas Git para hacer deploy:

```bash
# En tu local
git add .
git commit -m "Add file management module"
git push origin main

# En el servidor
cd /var/www/html/reportesIA
git pull origin main
php artisan route:clear
php artisan config:clear
php artisan view:clear
php artisan cache:clear
composer dump-autoload
php artisan migrate
sudo chown -R www-data:www-data storage
sudo chmod -R 775 storage
sudo systemctl restart apache2
```

---

## ✅ CHECKLIST FINAL

Marca cada paso cuando lo completes:

- [ ] Subir `ReportTypeFileController.php`
- [ ] Subir `ReportTypeFile.php`
- [ ] Subir `ReportType.php` actualizado
- [ ] Subir migración `2026_01_05_165639_create_report_type_files_table.php`
- [ ] Subir `routes/web.php` actualizado
- [ ] Subir las 3 vistas de `resources/views/admin/report-files/`
- [ ] Subir `sidebar.blade.php` actualizado
- [ ] Ejecutar `php artisan route:clear`
- [ ] Ejecutar `php artisan config:clear`
- [ ] Ejecutar `php artisan view:clear`
- [ ] Ejecutar `php artisan cache:clear`
- [ ] Ejecutar `composer dump-autoload`
- [ ] Ejecutar `php artisan migrate`
- [ ] Configurar permisos de storage
- [ ] Verificar ruta con `php artisan route:list | grep report-files`
- [ ] Reiniciar Apache/Nginx/PHP-FPM
- [ ] Probar en el navegador: `https://mfg.blmovil.com/admin/report-files`

---

## 🎯 RESUMEN

El error ocurre porque:
1. El servidor no tiene el archivo `routes/web.php` actualizado
2. O el cache de rutas está desactualizado

**Solución:**
1. Subir TODOS los archivos listados arriba
2. Ejecutar `php artisan route:clear` (CRÍTICO)
3. Ejecutar la migración
4. Configurar permisos

**Tiempo estimado:** 5-10 minutos

---

## 📞 SOPORTE

Si después de seguir todos los pasos el error persiste:

1. Verifica los logs de Laravel:
   ```bash
   tail -f storage/logs/laravel.log
   ```

2. Verifica los logs de Apache:
   ```bash
   sudo tail -f /var/log/apache2/error.log
   ```

3. Asegúrate de que el usuario `www-data` tenga permisos en todos los archivos.

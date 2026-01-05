# 🚨 SOLUCIÓN RÁPIDA - Error Route [admin.report-files.index] not defined

## ⚡ OPCIÓN 1: Script Automático (RECOMENDADO)

### 1. Subir archivos al servidor
Sube estos archivos a tu servidor `mfg.blmovil.com`:

```
app/Http/Controllers/ReportTypeFileController.php
app/Models/ReportTypeFile.php
app/Models/ReportType.php (actualizado)
database/migrations/2026_01_05_165639_create_report_type_files_table.php
routes/web.php (actualizado - MUY IMPORTANTE)
resources/views/admin/report-files/index.blade.php
resources/views/admin/report-files/show.blade.php
resources/views/admin/report-files/create.blade.php
resources/views/components/crm/sidebar.blade.php (actualizado)
deploy-fix.sh (el script)
```

### 2. Conectarse al servidor y ejecutar el script
```bash
ssh usuario@mfg.blmovil.com
cd /ruta/a/reportesIA
bash deploy-fix.sh
```

### 3. Reiniciar Apache
```bash
sudo systemctl restart apache2
```

### 4. Probar
Ir a: `https://mfg.blmovil.com/admin/report-files`

---

## ⚡ OPCIÓN 2: Comandos Manuales

Si prefieres ejecutar los comandos uno por uno:

```bash
# 1. Conectarse al servidor
ssh usuario@mfg.blmovil.com

# 2. Ir al directorio del proyecto
cd /var/www/html/reportesIA  # Ajusta según tu instalación

# 3. Limpiar caches (MUY IMPORTANTE)
php artisan route:clear
php artisan config:clear
php artisan view:clear
php artisan cache:clear
composer dump-autoload

# 4. Ejecutar migración
php artisan migrate

# 5. Crear directorio y permisos
sudo mkdir -p storage/app/report_files
sudo chown -R www-data:www-data storage
sudo chmod -R 775 storage

# 6. Verificar rutas
php artisan route:list | grep report-files

# 7. Reiniciar Apache
sudo systemctl restart apache2

# 8. Probar
# Ir a: https://mfg.blmovil.com/admin/report-files
```

---

## 📋 ARCHIVOS QUE DEBES SUBIR

### Críticos (sin estos NO funcionará):
1. ✅ `routes/web.php` - **MUY IMPORTANTE** (contiene las nuevas rutas)
2. ✅ `app/Http/Controllers/ReportTypeFileController.php` - Controlador nuevo
3. ✅ `app/Models/ReportTypeFile.php` - Modelo nuevo

### Importantes:
4. ✅ `database/migrations/2026_01_05_165639_create_report_type_files_table.php`
5. ✅ `app/Models/ReportType.php` - Modelo actualizado
6. ✅ `resources/views/admin/report-files/index.blade.php`
7. ✅ `resources/views/admin/report-files/show.blade.php`
8. ✅ `resources/views/admin/report-files/create.blade.php`
9. ✅ `resources/views/components/crm/sidebar.blade.php` - Sidebar actualizado

---

## 🔍 VERIFICACIÓN

Después de ejecutar los pasos, verifica:

### 1. Las rutas existen:
```bash
php artisan route:list | grep report-files
```

Debes ver 6 rutas:
```
admin.report-files.index
admin.report-files.show
admin.report-files.create
admin.report-files.store
admin.report-files.download
admin.report-files.destroy
```

### 2. La tabla existe:
```bash
php artisan tinker
```
```php
Schema::hasTable('report_type_files');  // Debe retornar: true
exit
```

### 3. El archivo web.php tiene las rutas:
```bash
cat routes/web.php | grep report-files
```

---

## ❓ PREGUNTAS FRECUENTES

**P: ¿Por qué sale este error?**
R: Porque el servidor no tiene el archivo `routes/web.php` actualizado con las nuevas rutas.

**P: ¿Ya subí los archivos pero sigue el error?**
R: Ejecuta `php artisan route:clear` - Laravel cachea las rutas.

**P: ¿Cómo sé si subí bien los archivos?**
R: Ejecuta `ls -la app/Http/Controllers/ReportTypeFileController.php`

**P: ¿Qué usuario usar para los permisos?**
R: Generalmente `www-data` en Ubuntu/Debian o `apache` en CentOS/RHEL.

---

## 🎯 CAUSA RAÍZ

El error ocurre porque:

1. Laravel busca la ruta `admin.report-files.index` en el sidebar
2. Esa ruta se define en `routes/web.php` línea 40
3. Tu servidor NO tiene ese archivo actualizado
4. O el cache de rutas está desactualizado

**Solución:** Subir `routes/web.php` + limpiar cache de rutas.

---

## ✅ CHECKLIST RÁPIDO

- [ ] Subir `routes/web.php` actualizado
- [ ] Subir `ReportTypeFileController.php`
- [ ] Subir `ReportTypeFile.php`
- [ ] Ejecutar `php artisan route:clear`
- [ ] Ejecutar `php artisan migrate`
- [ ] Reiniciar Apache
- [ ] Probar en navegador

**Tiempo:** 5 minutos

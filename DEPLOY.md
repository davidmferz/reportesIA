# Guía de Deploy para reportesIA

## Pasos para hacer el deploy en el servidor

### 1. Subir el archivo de configuración de Apache

```bash
# En tu servidor, copia el archivo reportesIA.conf a sites-available
sudo cp reportesIA.conf /etc/apache2/sites-available/

# Habilitar el sitio
sudo a2ensite reportesIA.conf

# Verificar la configuración
sudo apache2ctl configtest

# Si todo está OK, recargar Apache
sudo systemctl reload apache2
```

### 2. Configurar DNS

Antes de continuar, asegúrate de crear el registro DNS:
- **Tipo**: A o CNAME
- **Host**: reportesia
- **Apunta a**: La IP de tu servidor o blmovil.com

### 3. Configurar permisos de directorios

```bash
cd /var/www/html/reportesIA

# Dar permisos a www-data
sudo chown -R www-data:www-data storage bootstrap/cache
sudo chmod -R 775 storage bootstrap/cache

# Si hay problemas con otros directorios
sudo chown -R www-data:www-data .
sudo chmod -R 755 .
sudo chmod -R 775 storage bootstrap/cache
```

### 4. Instalar dependencias con PHP 8.2

```bash
# Verificar que PHP 8.2 esté instalado
php8.2 -v

# Instalar dependencias de Composer usando PHP 8.2
/usr/bin/php8.2 /usr/local/bin/composer install --optimize-autoloader --no-dev

# Si composer no está en /usr/local/bin, ubícalo con:
# which composer
```

### 5. Configurar variables de entorno

```bash
# Si necesitas generar la APP_KEY
/usr/bin/php8.2 artisan key:generate

# Verificar que el .env tenga las configuraciones correctas:
# - DB_CONNECTION
# - DB_HOST
# - DB_PORT
# - DB_DATABASE
# - DB_USERNAME
# - DB_PASSWORD
```

### 6. Ejecutar migraciones y optimizaciones

```bash
# Ejecutar migraciones
/usr/bin/php8.2 artisan migrate --force

# Limpiar cachés antiguos
/usr/bin/php8.2 artisan config:clear
/usr/bin/php8.2 artisan cache:clear
/usr/bin/php8.2 artisan view:clear
/usr/bin/php8.2 artisan route:clear

# Generar nuevos cachés (producción)
/usr/bin/php8.2 artisan config:cache
/usr/bin/php8.2 artisan route:cache
/usr/bin/php8.2 artisan view:cache

# Optimizar autoloader
/usr/bin/php8.2 /usr/local/bin/composer dump-autoload --optimize
```

### 7. Verificar instalación

Visita: https://reportesia.blmovil.com

### Solución de problemas comunes

#### Error 500 - Internal Server Error
```bash
# Ver logs de Apache
sudo tail -f /var/log/apache2/reportesia.blmovil.com-error_log

# Ver logs de Laravel
tail -f /var/www/html/reportesIA/storage/logs/laravel.log

# Verificar permisos
ls -la storage/
ls -la bootstrap/cache/
```

#### Error de base de datos
```bash
# Verificar conexión
/usr/bin/php8.2 artisan tinker
# Dentro de tinker: DB::connection()->getPdo();
```

#### PHP-FPM no responde
```bash
# Verificar que php8.2-fpm esté corriendo
sudo systemctl status php8.2-fpm

# Reiniciar si es necesario
sudo systemctl restart php8.2-fpm
```

#### Comandos útiles para mantenimiento

```bash
# Ver versión de PHP que usa la aplicación
/usr/bin/php8.2 artisan --version

# Limpiar todo y regenerar cachés
/usr/bin/php8.2 artisan optimize:clear
/usr/bin/php8.2 artisan optimize

# Ver rutas disponibles
/usr/bin/php8.2 artisan route:list

# Modo mantenimiento
/usr/bin/php8.2 artisan down
/usr/bin/php8.2 artisan up
```

## Checklist de Deploy

- [ ] Archivo de configuración Apache copiado y habilitado
- [ ] DNS configurado (reportesia.blmovil.com)
- [ ] Apache recargado sin errores
- [ ] Permisos de storage y bootstrap/cache configurados
- [ ] Dependencias de Composer instaladas con PHP 8.2
- [ ] Archivo .env configurado correctamente
- [ ] APP_KEY generada
- [ ] Migraciones ejecutadas
- [ ] Cachés generados
- [ ] Sitio accesible vía HTTPS
- [ ] Logs sin errores críticos

## Información del servidor

- **Dominio**: reportesia.blmovil.com
- **Document Root**: /var/www/html/reportesIA/public
- **PHP Version**: 8.2 (vía PHP-FPM)
- **Certificados SSL**: Wildcard _.blmovil.com (compartidos)

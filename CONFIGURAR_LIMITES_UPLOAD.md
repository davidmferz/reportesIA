# Configurar Límites de Subida de Archivos en el Servidor

## 🚨 PROBLEMA
Los archivos mayores a 5MB no se suben en el servidor, pero en local sí funcionan.

## 📋 CAUSA
PHP en el servidor tiene límites configurados más bajos que en tu entorno local. Necesitas aumentar los siguientes valores:

- `upload_max_filesize` (tamaño máximo por archivo)
- `post_max_size` (tamaño máximo del POST)
- `max_execution_time` (tiempo máximo de ejecución)
- `max_input_time` (tiempo máximo de recepción de datos)
- `memory_limit` (memoria máxima)

---

## ✅ SOLUCIÓN PASO A PASO

### 1️⃣ Encontrar el archivo php.ini

Primero, identifica qué archivo `php.ini` está usando tu servidor:

```bash
# Conectarse al servidor
ssh usuario@mfg.blmovil.com

# Encontrar el php.ini que se está usando
php -i | grep "Loaded Configuration File"
```

Posibles ubicaciones:
- `/etc/php/8.2/apache2/php.ini` (si usas Apache)
- `/etc/php/8.2/fpm/php.ini` (si usas PHP-FPM)
- `/etc/php/8.2/cli/php.ini` (línea de comandos)

---

### 2️⃣ Editar el archivo php.ini

```bash
# Para Apache (más común)
sudo nano /etc/php/8.2/apache2/php.ini

# O para PHP-FPM
sudo nano /etc/php/8.2/fpm/php.ini
```

Busca y modifica estas líneas (usa `Ctrl+W` para buscar en nano):

```ini
; Tamaño máximo de archivo a subir (50MB)
upload_max_filesize = 50M

; Tamaño máximo del POST (debe ser mayor o igual a upload_max_filesize)
post_max_size = 51M

; Tiempo máximo de ejecución de un script (5 minutos)
max_execution_time = 300

; Tiempo máximo para recibir datos (5 minutos)
max_input_time = 300

; Memoria máxima que puede usar un script
memory_limit = 256M
```

**IMPORTANTE:** Si las líneas tienen un `;` al inicio, quítalo (eso significa que están comentadas).

Guardar y salir:
- `Ctrl+O` (guardar)
- `Enter` (confirmar)
- `Ctrl+X` (salir)

---

### 3️⃣ Configurar Nginx (si usas Nginx)

Si tu servidor usa Nginx, también debes configurarlo:

```bash
sudo nano /etc/nginx/nginx.conf
```

Busca la sección `http` y agrega o modifica:

```nginx
http {
    # ... otras configuraciones ...

    # Tamaño máximo del body del cliente (50MB)
    client_max_body_size 50M;

    # Timeout para el cliente
    client_body_timeout 300s;
}
```

Guardar y salir.

---

### 4️⃣ Reiniciar Servicios

```bash
# Si usas Apache
sudo systemctl restart apache2

# Si usas PHP-FPM
sudo systemctl restart php8.2-fpm

# Si usas Nginx
sudo systemctl restart nginx
```

---

### 5️⃣ Verificar la Configuración

Crea un archivo para verificar la configuración:

```bash
cd /var/www/html/reportesIA/public
echo "<?php phpinfo(); ?>" | sudo tee info.php
```

Luego visita en tu navegador:
```
https://mfg.blmovil.com/info.php
```

Busca estas variables y verifica que tengan los valores correctos:
- `upload_max_filesize` → 50M
- `post_max_size` → 51M
- `max_execution_time` → 300
- `max_input_time` → 300

**IMPORTANTE:** Elimina el archivo después de verificar:
```bash
sudo rm /var/www/html/reportesIA/public/info.php
```

---

## 🚀 SCRIPT AUTOMÁTICO

Puedes usar este script para configurar todo automáticamente:

```bash
#!/bin/bash

# Encontrar el archivo php.ini
PHP_INI=$(php -i | grep "Loaded Configuration File" | cut -d'>' -f2 | xargs)

echo "Archivo php.ini encontrado: $PHP_INI"

# Hacer backup
sudo cp $PHP_INI $PHP_INI.backup.$(date +%Y%m%d)

# Configurar límites
sudo sed -i 's/^upload_max_filesize.*/upload_max_filesize = 50M/' $PHP_INI
sudo sed -i 's/^post_max_size.*/post_max_size = 51M/' $PHP_INI
sudo sed -i 's/^max_execution_time.*/max_execution_time = 300/' $PHP_INI
sudo sed -i 's/^max_input_time.*/max_input_time = 300/' $PHP_INI
sudo sed -i 's/^memory_limit.*/memory_limit = 256M/' $PHP_INI

echo "Configuración actualizada"

# Reiniciar servicios
echo "Reiniciando servicios..."
sudo systemctl restart apache2 2>/dev/null || echo "Apache no está instalado o no se pudo reiniciar"
sudo systemctl restart php8.2-fpm 2>/dev/null || echo "PHP-FPM no está instalado o no se pudo reiniciar"

echo "¡Listo! Verifica la configuración visitando https://mfg.blmovil.com/info.php"
```

Guarda este script como `config-upload.sh` y ejecútalo:

```bash
chmod +x config-upload.sh
sudo ./config-upload.sh
```

---

## 📊 VALORES RECOMENDADOS

| Variable | Valor Actual (probable) | Valor Recomendado | Propósito |
|----------|------------------------|-------------------|-----------|
| `upload_max_filesize` | 2M | **50M** | Tamaño máximo por archivo |
| `post_max_size` | 8M | **51M** | Tamaño máximo del POST |
| `max_execution_time` | 30 | **300** | Tiempo máximo de ejecución |
| `max_input_time` | 60 | **300** | Tiempo para recibir datos |
| `memory_limit` | 128M | **256M** | Memoria disponible |

---

## 🔍 DIAGNÓSTICO

### Verificar límites actuales desde terminal:

```bash
php -r "echo 'upload_max_filesize: ' . ini_get('upload_max_filesize') . PHP_EOL;"
php -r "echo 'post_max_size: ' . ini_get('post_max_size') . PHP_EOL;"
php -r "echo 'max_execution_time: ' . ini_get('max_execution_time') . PHP_EOL;"
php -r "echo 'memory_limit: ' . ini_get('memory_limit') . PHP_EOL;"
```

### Ver errores de PHP:

```bash
# Logs de Apache
sudo tail -f /var/log/apache2/error.log

# Logs de PHP-FPM
sudo tail -f /var/log/php8.2-fpm.log

# Logs de Laravel
tail -f storage/logs/laravel.log
```

---

## 🎯 CONFIGURACIÓN ALTERNATIVA (.htaccess)

Si **NO** tienes acceso al php.ini, puedes intentar configurarlo en `.htaccess`:

```bash
cd /var/www/html/reportesIA/public
sudo nano .htaccess
```

Agrega al inicio del archivo:

```apache
# Límites de upload
php_value upload_max_filesize 50M
php_value post_max_size 51M
php_value max_execution_time 300
php_value max_input_time 300
php_value memory_limit 256M
```

**NOTA:** Esto solo funciona si el servidor tiene `AllowOverride All` habilitado.

---

## ⚠️ CONFIGURACIÓN EN LARAVEL

También asegúrate de que Laravel esté configurado correctamente.

Edita el controlador si es necesario:

```php
// En ReportTypeFileController.php
$validated = $request->validate([
    'archivos.*' => 'required|file|max:51200', // 50MB en KB
], [
    'archivos.*.max' => 'El archivo no puede superar los 50MB.',
]);
```

Nuestro código ya tiene este límite configurado correctamente (línea 37 del controlador).

---

## ✅ CHECKLIST DE VERIFICACIÓN

Después de hacer los cambios:

- [ ] Editar `/etc/php/8.2/apache2/php.ini` (o fpm)
- [ ] Configurar `upload_max_filesize = 50M`
- [ ] Configurar `post_max_size = 51M`
- [ ] Configurar `max_execution_time = 300`
- [ ] Configurar `max_input_time = 300`
- [ ] Configurar `memory_limit = 256M`
- [ ] Si usas Nginx: configurar `client_max_body_size 50M`
- [ ] Reiniciar Apache/PHP-FPM/Nginx
- [ ] Verificar con `phpinfo()`
- [ ] Probar subir archivo de 10MB
- [ ] Eliminar archivo `info.php`

---

## 🆘 SOLUCIÓN DE PROBLEMAS

### Problema 1: No se aplican los cambios
**Solución:** Verifica que estás editando el php.ini correcto:
```bash
php -i | grep "Loaded Configuration File"
```

### Problema 2: Sigue limitado a 2MB
**Solución:** Puede haber múltiples php.ini. Edita TODOS:
```bash
sudo find /etc/php -name php.ini -exec sed -i 's/^upload_max_filesize.*/upload_max_filesize = 50M/' {} \;
```

### Problema 3: Error 413 (Payload Too Large)
**Solución:** Es un error de Nginx. Configura `client_max_body_size 50M` en nginx.conf.

### Problema 4: El script se corta a mitad de subida
**Solución:** Aumenta `max_execution_time` y `max_input_time` a 600 (10 minutos).

---

## 📞 RESUMEN EJECUTIVO

**Para el administrador del servidor:**

1. Editar `/etc/php/8.2/apache2/php.ini`
2. Cambiar estos valores:
   - `upload_max_filesize = 50M`
   - `post_max_size = 51M`
   - `max_execution_time = 300`
3. Reiniciar Apache: `sudo systemctl restart apache2`
4. Verificar en navegador con `phpinfo()`

**Tiempo:** 3-5 minutos

#!/bin/bash

# Script para configurar límites de subida de archivos en PHP
# Compatible con Ubuntu/Debian con PHP 8.2

echo "============================================"
echo "Configuración de Límites de Upload PHP 8.2"
echo "============================================"
echo ""

# Colores
GREEN='\033[0;32m'
RED='\033[0;31m'
YELLOW='\033[1;33m'
NC='\033[0m'

print_success() { echo -e "${GREEN}✓ $1${NC}"; }
print_error() { echo -e "${RED}✗ $1${NC}"; }
print_warning() { echo -e "${YELLOW}⚠ $1${NC}"; }

# Verificar si es root
if [ "$EUID" -ne 0 ]; then
    print_error "Este script debe ejecutarse como root (usa sudo)"
    exit 1
fi

# 1. Encontrar archivos php.ini
echo "1. Buscando archivos php.ini..."
PHP_INI_APACHE="/etc/php/8.2/apache2/php.ini"
PHP_INI_FPM="/etc/php/8.2/fpm/php.ini"
PHP_INI_CLI="/etc/php/8.2/cli/php.ini"

FILES_TO_UPDATE=()

if [ -f "$PHP_INI_APACHE" ]; then
    FILES_TO_UPDATE+=("$PHP_INI_APACHE")
    print_success "Encontrado: $PHP_INI_APACHE"
fi

if [ -f "$PHP_INI_FPM" ]; then
    FILES_TO_UPDATE+=("$PHP_INI_FPM")
    print_success "Encontrado: $PHP_INI_FPM"
fi

if [ -f "$PHP_INI_CLI" ]; then
    FILES_TO_UPDATE+=("$PHP_INI_CLI")
    print_success "Encontrado: $PHP_INI_CLI"
fi

if [ ${#FILES_TO_UPDATE[@]} -eq 0 ]; then
    print_error "No se encontraron archivos php.ini para PHP 8.2"
    exit 1
fi

echo ""

# 2. Hacer backup
echo "2. Creando backups..."
BACKUP_DATE=$(date +%Y%m%d_%H%M%S)
for file in "${FILES_TO_UPDATE[@]}"; do
    cp "$file" "${file}.backup.${BACKUP_DATE}"
    print_success "Backup creado: ${file}.backup.${BACKUP_DATE}"
done
echo ""

# 3. Actualizar configuraciones
echo "3. Actualizando configuraciones..."

for file in "${FILES_TO_UPDATE[@]}"; do
    echo "   Configurando: $file"

    # Actualizar o agregar las configuraciones
    sed -i 's/^upload_max_filesize.*/upload_max_filesize = 50M/' "$file"
    sed -i 's/^post_max_size.*/post_max_size = 51M/' "$file"
    sed -i 's/^max_execution_time.*/max_execution_time = 300/' "$file"
    sed -i 's/^max_input_time.*/max_input_time = 300/' "$file"
    sed -i 's/^memory_limit.*/memory_limit = 256M/' "$file"

    # Si no existen las líneas, agregarlas
    grep -q "^upload_max_filesize" "$file" || echo "upload_max_filesize = 50M" >> "$file"
    grep -q "^post_max_size" "$file" || echo "post_max_size = 51M" >> "$file"
    grep -q "^max_execution_time" "$file" || echo "max_execution_time = 300" >> "$file"
    grep -q "^max_input_time" "$file" || echo "max_input_time = 300" >> "$file"
    grep -q "^memory_limit" "$file" || echo "memory_limit = 256M" >> "$file"

    print_success "   ✓ Configurado"
done
echo ""

# 4. Configurar Nginx si existe
echo "4. Verificando Nginx..."
if [ -f "/etc/nginx/nginx.conf" ]; then
    print_warning "Nginx detectado. Configurando client_max_body_size..."

    # Backup de nginx.conf
    cp /etc/nginx/nginx.conf /etc/nginx/nginx.conf.backup.$BACKUP_DATE

    # Verificar si ya existe la configuración
    if grep -q "client_max_body_size" /etc/nginx/nginx.conf; then
        sed -i 's/client_max_body_size.*/client_max_body_size 50M;/' /etc/nginx/nginx.conf
        print_success "   client_max_body_size actualizado en nginx.conf"
    else
        # Agregar dentro del bloque http
        sed -i '/http {/a \    client_max_body_size 50M;' /etc/nginx/nginx.conf
        print_success "   client_max_body_size agregado a nginx.conf"
    fi
else
    print_success "Nginx no detectado (no es necesario configurarlo)"
fi
echo ""

# 5. Verificar configuración
echo "5. Verificando configuración actual de PHP..."
echo "   upload_max_filesize: $(php -r "echo ini_get('upload_max_filesize');")"
echo "   post_max_size: $(php -r "echo ini_get('post_max_size');")"
echo "   max_execution_time: $(php -r "echo ini_get('max_execution_time');")"
echo "   max_input_time: $(php -r "echo ini_get('max_input_time');")"
echo "   memory_limit: $(php -r "echo ini_get('memory_limit');")"
echo ""

# 6. Reiniciar servicios
echo "6. Reiniciando servicios..."

# Apache
if systemctl is-active --quiet apache2; then
    systemctl restart apache2
    print_success "Apache reiniciado"
else
    print_warning "Apache no está activo o no está instalado"
fi

# PHP-FPM
if systemctl is-active --quiet php8.2-fpm; then
    systemctl restart php8.2-fpm
    print_success "PHP-FPM reiniciado"
else
    print_warning "PHP-FPM no está activo o no está instalado"
fi

# Nginx
if systemctl is-active --quiet nginx; then
    nginx -t && systemctl restart nginx
    if [ $? -eq 0 ]; then
        print_success "Nginx reiniciado"
    else
        print_error "Error en configuración de Nginx. Verifica manualmente."
    fi
else
    print_warning "Nginx no está activo o no está instalado"
fi

echo ""
echo "============================================"
echo "✅ CONFIGURACIÓN COMPLETADA"
echo "============================================"
echo ""
echo "Cambios aplicados:"
echo "  • upload_max_filesize: 50M"
echo "  • post_max_size: 51M"
echo "  • max_execution_time: 300 segundos"
echo "  • max_input_time: 300 segundos"
echo "  • memory_limit: 256M"
if [ -f "/etc/nginx/nginx.conf" ]; then
    echo "  • client_max_body_size (Nginx): 50M"
fi
echo ""
echo "Backups creados en:"
for file in "${FILES_TO_UPDATE[@]}"; do
    echo "  • ${file}.backup.${BACKUP_DATE}"
done
echo ""
echo "Próximos pasos:"
echo "1. Probar subir un archivo mayor a 5MB en:"
echo "   https://mfg.blmovil.com/admin/report-files"
echo ""
echo "2. Si quieres verificar la configuración en el navegador:"
echo "   - Crea: echo '<?php phpinfo(); ?>' > /var/www/html/reportesIA/public/info.php"
echo "   - Visita: https://mfg.blmovil.com/info.php"
echo "   - ELIMINA el archivo después: rm /var/www/html/reportesIA/public/info.php"
echo ""
echo "============================================"

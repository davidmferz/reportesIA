#!/bin/bash

# Script para solucionar el error de rutas no definidas
# Ejecutar en el servidor después de subir los archivos nuevos

echo "================================================"
echo "Deploy del Módulo de Gestión de Archivos"
echo "================================================"
echo ""

# Colores para output
GREEN='\033[0;32m'
RED='\033[0;31m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Función para imprimir con color
print_success() {
    echo -e "${GREEN}✓ $1${NC}"
}

print_error() {
    echo -e "${RED}✗ $1${NC}"
}

print_warning() {
    echo -e "${YELLOW}⚠ $1${NC}"
}

# Verificar que estamos en el directorio correcto
if [ ! -f "artisan" ]; then
    print_error "No se encontró el archivo artisan. Asegúrate de estar en el directorio raíz del proyecto Laravel."
    exit 1
fi

print_success "Directorio correcto detectado"
echo ""

# 1. Limpiar caches
echo "1. Limpiando caches de Laravel..."
php artisan route:clear && print_success "Cache de rutas limpiado" || print_error "Error al limpiar cache de rutas"
php artisan config:clear && print_success "Cache de configuración limpiado" || print_error "Error al limpiar cache de configuración"
php artisan view:clear && print_success "Cache de vistas limpiado" || print_error "Error al limpiar cache de vistas"
php artisan cache:clear && print_success "Cache general limpiado" || print_error "Error al limpiar cache general"
echo ""

# 2. Composer autoload
echo "2. Regenerando autoload de Composer..."
composer dump-autoload && print_success "Autoload regenerado" || print_error "Error al regenerar autoload"
echo ""

# 3. Verificar archivos críticos
echo "3. Verificando archivos críticos..."
FILES=(
    "app/Http/Controllers/ReportTypeFileController.php"
    "app/Models/ReportTypeFile.php"
    "routes/web.php"
    "resources/views/admin/report-files/index.blade.php"
    "resources/views/admin/report-files/show.blade.php"
    "resources/views/admin/report-files/create.blade.php"
)

for file in "${FILES[@]}"; do
    if [ -f "$file" ]; then
        print_success "Archivo encontrado: $file"
    else
        print_error "Archivo NO encontrado: $file"
        print_warning "Debes subir este archivo al servidor"
    fi
done
echo ""

# 4. Ejecutar migración
echo "4. Ejecutando migración..."
php artisan migrate --force && print_success "Migración ejecutada correctamente" || print_warning "Error en la migración (puede ser que ya esté ejecutada)"
echo ""

# 5. Crear y configurar directorio de archivos
echo "5. Configurando directorio de almacenamiento..."
mkdir -p storage/app/report_files && print_success "Directorio storage/app/report_files creado"

# Intentar configurar permisos (puede requerir sudo)
if [ "$EUID" -eq 0 ]; then
    chown -R www-data:www-data storage
    chmod -R 775 storage
    print_success "Permisos configurados correctamente"
else
    print_warning "No tienes permisos de root. Ejecuta manualmente:"
    echo "  sudo chown -R www-data:www-data storage"
    echo "  sudo chmod -R 775 storage"
fi
echo ""

# 6. Verificar rutas
echo "6. Verificando rutas registradas..."
ROUTES=$(php artisan route:list | grep -c "report-files")
if [ "$ROUTES" -gt 0 ]; then
    print_success "Rutas de gestión de archivos encontradas ($ROUTES rutas)"
    echo ""
    php artisan route:list | grep "report-files"
else
    print_error "No se encontraron las rutas de gestión de archivos"
    print_warning "Verifica que el archivo routes/web.php esté actualizado"
fi
echo ""

# 7. Verificar tabla en la base de datos
echo "7. Verificando tabla en la base de datos..."
php artisan tinker --execute="echo Schema::hasTable('report_type_files') ? 'Tabla existe' : 'Tabla NO existe';" && echo "" || print_error "Error al verificar la tabla"
echo ""

# 8. Reiniciar servicios (opcional)
echo "8. Reiniciando servicios..."
print_warning "Para reiniciar servicios, ejecuta manualmente (requiere sudo):"
echo "  sudo systemctl restart apache2    # Si usas Apache"
echo "  sudo systemctl restart php8.2-fpm # Si usas PHP-FPM"
echo "  sudo systemctl restart nginx      # Si usas Nginx"
echo ""

# Resumen final
echo "================================================"
echo "RESUMEN"
echo "================================================"
print_success "Caches limpiados"
print_success "Autoload regenerado"

if [ "$ROUTES" -gt 0 ]; then
    print_success "Rutas verificadas ($ROUTES encontradas)"
else
    print_error "Rutas NO encontradas - REVISAR"
fi

echo ""
echo "Próximos pasos:"
echo "1. Reiniciar Apache/Nginx/PHP-FPM (con sudo)"
echo "2. Probar en el navegador: https://mfg.blmovil.com/admin/report-files"
echo ""
echo "================================================"

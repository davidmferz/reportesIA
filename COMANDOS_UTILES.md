# Comandos Útiles - ReportesIA

## Desarrollo Diario

### Iniciar el servidor
```bash
php artisan serve
```

### Compilar assets en desarrollo (con watch)
```bash
npm run dev
```

### Compilar assets para producción
```bash
npm run build
```

## Base de Datos

### Ejecutar migraciones
```bash
php artisan migrate
```

### Revertir última migración
```bash
php artisan migrate:rollback
```

### Resetear toda la base de datos y ejecutar seeders
```bash
php artisan migrate:fresh --seed
```

### Ejecutar solo los seeders
```bash
php artisan db:seed
```

### Crear nueva migración
```bash
php artisan make:migration nombre_de_migracion
```

### Crear modelo con migración
```bash
php artisan make:model NombreModelo -m
```

## Desarrollo

### Crear controlador
```bash
php artisan make:controller NombreController
```

### Crear controlador de recursos (CRUD)
```bash
php artisan make:controller NombreController --resource
```

### Crear middleware
```bash
php artisan make:middleware NombreMiddleware
```

### Crear seeder
```bash
php artisan make:seeder NombreSeeder
```

### Listar todas las rutas
```bash
php artisan route:list
```

### Listar rutas de admin
```bash
php artisan route:list --name=admin
```

### Limpiar caché
```bash
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear
```

## Testing y Debugging

### Abrir consola interactiva (tinker)
```bash
php artisan tinker
```

### Ejemplos en tinker:
```php
// Ver todos los usuarios
User::all();

// Ver usuario admin
User::where('is_admin', true)->first();

// Ver logs de actividad
ActivityLog::with('causer')->latest()->take(10)->get();

// Crear usuario de prueba
User::create([
    'name' => 'Prueba',
    'email' => 'test@test.com',
    'password' => Hash::make('password'),
    'is_admin' => false
]);
```

## Producción

### Optimizar aplicación para producción
```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
composer install --optimize-autoloader --no-dev
```

### Poner aplicación en modo mantenimiento
```bash
php artisan down
```

### Sacar aplicación del modo mantenimiento
```bash
php artisan up
```

## Credenciales por Defecto

**Usuario Administrador:**
- Email: `admin@reportesia.com`
- Password: `password`

**IMPORTANTE:** Cambiar estas credenciales en producción.

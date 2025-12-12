# AGENTE.MD - Documentación del Proyecto ReportesIA

## Información General del Proyecto

**Nombre:** ReportesIA  
**Framework:** Laravel 12.x  
**Stack de Autenticación:** Laravel Breeze con Blade + Alpine.js  
**Base de Datos:** SQLite (development)  
**Versión PHP:** 8.2+  
**Tailwind CSS:** 3.x con soporte para modo oscuro  

---

## Arquitectura del Sistema

### 1. Sistema de Autenticación

El proyecto utiliza **Laravel Breeze** configurado con el stack Blade y Alpine.js para proporcionar:

- Registro de usuarios
- Login/Logout
- Recuperación de contraseña
- Verificación de email (opcional)
- Gestión de perfil

**Ubicación de archivos:**
- Controladores: `app/Http/Controllers/Auth/`
- Vistas: `resources/views/auth/`
- Rutas: `routes/auth.php`

---

### 2. Sistema de Roles y Permisos

#### Modelo de Usuario (User)

**Ubicación:** `app/Models/User.php`

**Campos adicionales:**
- `is_admin` (boolean): Indica si el usuario es administrador

**Métodos importantes:**
```php
public function isAdmin(): bool // Verifica si el usuario es administrador
public function activityLogs() // Relación con logs de actividad
```

**Traits implementados:**
- `HasFactory`
- `Notifiable`
- `LogsActivity` (Custom) - Auditoría automática

#### Middleware de Administrador

**Ubicación:** `app/Http/Middleware/EnsureUserIsAdmin.php`

**Alias:** `admin`

**Uso:**
```php
Route::middleware(['auth', 'admin'])->group(function () {
    // Rutas protegidas para administradores
});
```

---

### 3. Sistema de Auditoría

#### Modelo ActivityLog

**Ubicación:** `app/Models/ActivityLog.php`

**Tabla:** `activity_logs`

**Campos:**
- `id`: ID único
- `log_name`: Nombre del log (ej: "User")
- `description`: Descripción de la acción
- `subject_type` y `subject_id`: Relación polimórfica con el modelo afectado
- `causer_type` y `causer_id`: Relación polimórfica con el usuario que causó la acción
- `properties`: JSON con datos adicionales (atributos, valores antiguos)
- `event`: Tipo de evento (created, updated, deleted)
- `created_at` y `updated_at`

**Relaciones:**
```php
public function subject(): MorphTo // Modelo afectado
public function causer(): MorphTo // Usuario que causó la acción
```

**Scopes disponibles:**
```php
ActivityLog::forLog('User') // Filtrar por nombre de log
ActivityLog::causedBy($user) // Filtrar por usuario que causó la acción
```

#### Trait LogsActivity

**Ubicación:** `app/Traits/LogsActivity.php`

**Descripción:** Trait reutilizable que registra automáticamente las acciones de creación, actualización y eliminación en cualquier modelo.

**Uso:**
```php
use App\Traits\LogsActivity;

class MiModelo extends Model
{
    use LogsActivity;
}
```

**Eventos registrados automáticamente:**
- `created`: Registra la creación del modelo con todos sus atributos
- `updated`: Registra la actualización con valores antiguos y nuevos
- `deleted`: Registra la eliminación del modelo

---

### 4. Módulo de Gestión de Usuarios (CRUD)

#### Controlador

**Ubicación:** `app/Http/Controllers/Admin/UserController.php`

**Métodos:**

| Método | Ruta | Descripción |
|--------|------|-------------|
| `index()` | GET /admin/users | Lista todos los usuarios con paginación |
| `create()` | GET /admin/users/create | Formulario de creación |
| `store()` | POST /admin/users | Crea un nuevo usuario |
| `show($user)` | GET /admin/users/{user} | Muestra detalles y log de auditoría |
| `edit($user)` | GET /admin/users/{user}/edit | Formulario de edición |
| `update($user)` | PUT /admin/users/{user} | Actualiza un usuario |
| `destroy($user)` | DELETE /admin/users/{user} | Elimina un usuario |

**Validaciones implementadas:**
- Nombre: requerido, string, máx 255
- Email: requerido, email, único en la base de datos
- Password: requerido en creación, confirmación requerida, reglas de Laravel
- is_admin: boolean

**Protecciones:**
- No se puede eliminar el propio usuario
- Todas las rutas requieren autenticación y rol de administrador

#### Vistas

**Ubicación:** `resources/views/admin/users/`

| Vista | Descripción |
|-------|-------------|
| `index.blade.php` | Listado de usuarios en tabla con acciones |
| `create.blade.php` | Formulario de creación de usuario |
| `edit.blade.php` | Formulario de edición de usuario |
| `show.blade.php` | Detalles del usuario y log de auditoría |

**Características de las vistas:**
- Diseño responsive con Tailwind CSS
- Soporte para modo oscuro
- Mensajes flash para éxito/error
- Paginación integrada
- Confirmación de eliminación con JavaScript

#### Rutas

**Ubicación:** `routes/web.php`

```php
Route::middleware(['auth', 'admin'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {
        Route::resource('users', UserController::class);
    });
```

**URLs generadas:**
- GET `/admin/users` - admin.users.index
- GET `/admin/users/create` - admin.users.create
- POST `/admin/users` - admin.users.store
- GET `/admin/users/{user}` - admin.users.show
- GET `/admin/users/{user}/edit` - admin.users.edit
- PUT `/admin/users/{user}` - admin.users.update
- DELETE `/admin/users/{user}` - admin.users.destroy

---

### 5. Navegación

**Ubicación:** `resources/views/layouts/navigation.blade.php`

**Características:**
- Enlace "Usuarios" visible solo para administradores
- Navegación responsive con Alpine.js
- Indicador de ruta activa
- Dropdown de perfil de usuario

---

### 6. Base de Datos

#### Migraciones

**Ubicación:** `database/migrations/`

| Migración | Descripción |
|-----------|-------------|
| `0001_01_01_000000_create_users_table` | Tabla de usuarios (Laravel default) |
| `2025_12_12_025042_add_is_admin_to_users_table` | Agrega campo is_admin |
| `2025_12_12_025106_create_activity_logs_table` | Tabla de logs de auditoría |

#### Seeders

**Ubicación:** `database/seeders/`

**AdminUserSeeder:**
- Crea usuario administrador por defecto
- Email: `admin@reportesia.com`
- Password: `password`
- is_admin: `true`

**Ejecución:**
```bash
php artisan migrate:fresh --seed
```

---

## Patrones y Buenas Prácticas Implementadas

### 1. **Repository Pattern (Listo para implementar)**
Los controladores están preparados para extraer la lógica a repositorios si el proyecto crece.

### 2. **Service Layer (Listo para implementar)**
La lógica de negocio compleja puede moverse a clases de servicio.

### 3. **Trait Reutilizable**
`LogsActivity` puede ser usado en cualquier modelo para auditoría automática.

### 4. **Form Requests (Preparado)**
Las validaciones están en los controladores, pero pueden moverse a FormRequests dedicados.

### 5. **Route Model Binding**
Laravel resuelve automáticamente los modelos User en las rutas.

### 6. **Eloquent Relationships**
Relaciones polimórficas para el sistema de auditoría.

### 7. **Middleware Aliasing**
Middleware registrado con alias para uso sencillo.

### 8. **Scope Methods**
Métodos de scope en ActivityLog para queries reutilizables.

---

## Configuración del Proyecto

### Variables de Entorno (.env)

```env
APP_NAME=ReportesIA
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost

DB_CONNECTION=sqlite
# DB_DATABASE se genera automáticamente en database/database.sqlite
```

### Instalación desde Cero

```bash
# Clonar el repositorio (si aplica)
git clone <url>

# Instalar dependencias PHP
composer install

# Instalar dependencias NPM
npm install

# Copiar archivo de entorno
cp .env.example .env

# Generar key de aplicación
php artisan key:generate

# Crear base de datos SQLite
touch database/database.sqlite

# Ejecutar migraciones y seeders
php artisan migrate:fresh --seed

# Compilar assets
npm run build

# Iniciar servidor de desarrollo
php artisan serve
```

---

## Estructura de Directorios Clave

```
reportesIA/
├── app/
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── Admin/
│   │   │   │   └── UserController.php
│   │   │   └── Auth/ (Breeze)
│   │   └── Middleware/
│   │       └── EnsureUserIsAdmin.php
│   ├── Models/
│   │   ├── User.php
│   │   └── ActivityLog.php
│   └── Traits/
│       └── LogsActivity.php
├── database/
│   ├── migrations/
│   └── seeders/
│       ├── DatabaseSeeder.php
│       └── AdminUserSeeder.php
├── resources/
│   └── views/
│       ├── admin/
│       │   └── users/
│       │       ├── index.blade.php
│       │       ├── create.blade.php
│       │       ├── edit.blade.php
│       │       └── show.blade.php
│       ├── auth/ (Breeze)
│       └── layouts/
│           ├── app.blade.php
│           └── navigation.blade.php
└── routes/
    ├── web.php
    └── auth.php
```

---

## Guía para Agregar Nuevas Funcionalidades

### 1. Agregar un Nuevo Módulo CRUD

```bash
# Crear controlador
php artisan make:controller Admin/MiModuloController --resource

# Crear modelo con migración
php artisan make:model MiModelo -m

# Agregar trait de auditoría al modelo
use App\Traits\LogsActivity;
use LogsActivity;

# Agregar rutas en routes/web.php
Route::middleware(['auth', 'admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::resource('mimodulo', MiModuloController::class);
});

# Crear vistas en resources/views/admin/mimodulo/
```

### 2. Agregar Nuevos Roles

Si en el futuro necesitas más roles más allá de admin/user:

```bash
# Crear migración para tabla roles
php artisan make:migration create_roles_table

# Crear modelo Role
php artisan make:model Role

# Implementar relación many-to-many con User
# Considerar usar paquetes como Spatie Laravel Permission
```

### 3. Agregar Validaciones Personalizadas

```bash
# Crear FormRequest
php artisan make:request StoreUserRequest

# Mover validaciones del controlador al FormRequest
# Usar el FormRequest en el método del controlador
```

---

## Testing

### Datos de Prueba

**Usuario Administrador:**
- Email: `admin@reportesia.com`
- Password: `password`

### Comandos Útiles

```bash
# Crear usuario de prueba manualmente
php artisan tinker
User::create([
    'name' => 'Usuario Prueba',
    'email' => 'prueba@test.com',
    'password' => Hash::make('password'),
    'is_admin' => false
]);

# Ver logs de actividad
ActivityLog::with('causer')->latest()->get();

# Limpiar y regenerar base de datos
php artisan migrate:fresh --seed
```

---

## Seguridad

### Implementaciones de Seguridad

1. **Protección CSRF:** Tokens CSRF en todos los formularios
2. **Hashing de Contraseñas:** Bcrypt por defecto
3. **Mass Assignment Protection:** $fillable en modelos
4. **SQL Injection Prevention:** Eloquent ORM
5. **XSS Protection:** Blade escapea por defecto con `{{ }}`
6. **Middleware de Autenticación:** Rutas protegidas con `auth`
7. **Middleware de Autorización:** Rutas admin protegidas con `admin`

### Recomendaciones

- Cambiar las credenciales por defecto en producción
- Usar HTTPS en producción
- Configurar rate limiting en rutas sensibles
- Implementar autenticación de dos factores (Laravel Fortify)
- Revisar logs de auditoría regularmente

---

## Escalabilidad

El proyecto está preparado para escalar mediante:

1. **Caching:** Redis/Memcached para sesiones y caché
2. **Queue Jobs:** Para operaciones pesadas (emails, reportes)
3. **API REST:** Estructura preparada para agregar API con Sanctum
4. **Multi-tenancy:** Arquitectura permite agregar tenants si es necesario
5. **Microservicios:** Módulos independientes facilitan separación futura

---

## Próximos Pasos Sugeridos

1. **Implementar roles avanzados** con Spatie Laravel Permission
2. **Agregar API REST** con Laravel Sanctum
3. **Implementar notificaciones** (email, SMS, push)
4. **Agregar sistema de reportes** (PDF, Excel)
5. **Implementar Dashboard** con estadísticas
6. **Testing automatizado** con PHPUnit/Pest
7. **CI/CD Pipeline** con GitHub Actions
8. **Dockerización** del proyecto

---

## Contacto y Soporte

Para dudas sobre la implementación, revisar:
- Documentación de Laravel: https://laravel.com/docs
- Documentación de Breeze: https://laravel.com/docs/starter-kits
- Documentación de Tailwind CSS: https://tailwindcss.com/docs

---

**Última actualización:** 2025-12-11  
**Versión del documento:** 1.0  
**Desarrollado con IA asistida**

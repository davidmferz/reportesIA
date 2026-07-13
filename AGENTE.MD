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

### 7. Sistema de Licenciamiento

El acceso a la aplicación depende de una licencia firmada que emite un
licensing-server externo (proyecto separado). El cliente la verifica
**offline**: no hay una llamada de red en cada request, solo lectura del
estado persistido localmente.

**Piezas principales:**

| Pieza | Ubicación |
|-------|-----------|
| Configuración | `config/license.php` |
| Verificador criptográfico (JWS/EdDSA) | `app/Services/LicenseVerifier.php` |
| Estado persistido (fila única) | `app/Models/LicenseState.php` → tabla `license_states` |
| Enforcement por request | `app/Http/Middleware/EnsureLicenseIsValid.php` (alias `license`) |
| Usuario desactivado | `app/Http/Middleware/EnsureUserIsActive.php` (alias `active`) |
| Pantallas de activación / bloqueo | `app/Http/Controllers/LicenseController.php` + `resources/views/license/` |
| Phone-home diario | `app/Console/Commands/LicenseCheckCommand.php` (`license:check`) |
| Generador de token dev | `scripts/license/make-dev-token.php` |

#### Variables de entorno (`config/license.php`)

| Variable | Default | Qué hace |
|----------|---------|----------|
| `LICENSE_SERVER_URL` | — | Base URL del licensing-server. `license:check` llama `POST {server_url}/api/v1/licenses/{client_id}/validate`. |
| `LICENSE_CLIENT_ID` | — | Identidad de este cliente frente al servidor. |
| `LICENSE_PRODUCT_ID` | — | Identifica el producto; también se usa para derivar la URL de la JWKS si `LICENSE_JWKS_URL` está vacía. |
| `LICENSE_REVALIDATION_HOURS` | `24` | Cada cuántas horas se espera que corra `license:check` (el scheduler real está fijado a `->daily()` en `bootstrap/app.php`). |
| `LICENSE_GRACE_DAYS` | `14` | Días de gracia después de `valid_until` durante los que la app sigue funcionando (con banner de aviso) aunque no se pueda revalidar contra el servidor. |
| `LICENSE_KID` / `LICENSE_PUBLIC_KEY` | — | Ranura 1 de clave pública pineada: `kid` + clave Ed25519 en base64url. |
| `LICENSE_KID_2` / `LICENSE_PUBLIC_KEY_2` | — | Ranura 2 de clave pública pineada. |
| `LICENSE_JWKS_URL` | derivada de `server_url` + `product_id` | Endpoint JWKS del servidor, para rotar claves sin redeploy del cliente. |
| `LICENSE_JWKS_CACHE_HOURS` | `24` | Horas que se cachea la respuesta de la JWKS antes de volver a pedirla. |
| `LICENSE_TRUST_NETWORK_JWKS` | `false` | Si es `true`, una clave traída por red (JWKS) puede pisar una clave pineada con el mismo `kid`. Por defecto una clave pineada localmente **siempre** gana, aunque la JWKS de red traiga otra para ese `kid`. |

El verificador selecciona la clave pública a usar por el header `kid` del
token (nunca por posición ni por "la última que llegó"), así que agregar o
rotar ranuras es seguro mientras el `kid` no cambie de significado.

#### Por qué hay DOS ranuras de clave pineada

Con una sola ranura, activar la clave pública del licensing-server real
pisaría de golpe la clave del helper de desarrollo (`make-dev-token.php`) —
y viceversa. Cualquier licencia ya emitida con la clave que quedó afuera se
invalidaría instantáneamente. Con dos ranuras (`LICENSE_KID`/`LICENSE_PUBLIC_KEY`
y `LICENSE_KID_2`/`LICENSE_PUBLIC_KEY_2`) ambas claves conviven: el
verificador elige cuál usar mirando el `kid` del token, no una posición fija.
Esto permite tener activada al mismo tiempo una licencia real del servidor y
una de prueba generada localmente, sin que una invalide a la otra.

#### Enforcement por request (middleware `license`)

`EnsureLicenseIsValid` NO hace red ni verificación criptográfica en cada
request: solo lee la fila persistida (`LicenseState::current()`) y resuelve
un estado local (`Valid` / `Grace` / `Blocked` / `Unlicensed`) comparando
fechas contra el reloj del servidor. Según el resultado:

- **Valid:** deja pasar.
- **Grace** (venció `valid_until` pero todavía está dentro de `LICENSE_GRACE_DAYS`): deja pasar y comparte `licenseGraceUntil` con las vistas para mostrar el banner de aviso.
- **Blocked / Unlicensed:** redirige. Si el usuario es admin, a `license.activation.show` (para que pueda reactivar); si no es admin, a `license.blocked.show` (pantalla read-only de "contactá a tu administrador" — nunca un 403).

Las pantallas de licencia (`license.activation.show`, `license.blocked.show`)
y las rutas de `routes/auth.php` (login, registro, recuperación de
contraseña) quedan **fuera** del middleware `license` a propósito: si no
fuera así, un admin bloqueado nunca podría loguearse ni llegar a la pantalla
de activación para resolver el bloqueo.

#### `license:check` y el phone-home diario

`bootstrap/app.php` agenda `license:check` para correr una vez por día
(`$schedule->command('license:check')->daily()`). Este comando sí hace red:
llama al licensing-server, y según la respuesta renueva, revoca o deja sin
cambios el estado local si el servidor no responde (fail-safe: un servidor
caído no tira abajo la app, el estado persistido queda intacto y sigue
gobernando el enforcement offline).

**Importante — la revocación NO es inmediata.** El enforcement de cada
request es offline por diseño (el licensing-server deliberadamente no es un
punto único de falla: si se cae, la app no debe dejar de funcionar). Eso
significa que si una licencia se revoca en el servidor, el cliente no se
entera hasta la próxima corrida de `license:check` (como máximo, una vez por
día). Esto es intencional, no un bug: hay que documentarlo y comunicarlo,
no ocultarlo.

#### Flujo de activación

1. Un admin inicia sesión (`login` funciona incluso con la licencia
   bloqueada, ver arriba).
2. Si no hay licencia vigente, se lo redirige a `license.activation.show`
   (`GET /license/activation`).
3. Pega el token JWS que le dio el licensing-server (o el helper de dev) en
   el formulario y lo envía (`POST /license/activation`).
4. `LicenseController::store()` llama a `LicenseVerifier::activate()`: si el
   token es inválido (firma, claims faltantes, dominio que no coincide,
   etc.) no se persiste nada y se muestra el error. Si es válido, reemplaza
   el estado de licencia y audita el alta en `activity_logs`.
5. Un usuario no-admin nunca ve esta pantalla: si la licencia está
   bloqueada, se lo manda a `license.blocked.show`, una pantalla read-only
   que le pide contactar a su administrador.

#### Generar un token de licencia sin el servidor corriendo

Para probar el cliente offline, sin levantar el licensing-server:

```bash
php scripts/license/make-dev-token.php [domain] [days] [maxUsers] [clientId]
```

Sin argumentos usa el host de `APP_URL`, 365 días, 25 usuarios y
`LICENSE_CLIENT_ID` (o `reportesia`). El script genera (o reutiliza) un
keypair Ed25519 local en `storage/license-dev/` — la clave privada nunca se
commitea — y firma un JWS de licencia igual al que emitiría el servidor real
(mismos claims, mismo `alg=EdDSA`, header `kid`). Al final imprime:

1. Las dos líneas para pegar en `.env` (`LICENSE_KID` / `LICENSE_PUBLIC_KEY`)
   seguidas de `php artisan config:clear`.
2. El token JWS para pegar en `/license/activation` una vez logueado como
   admin.

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

Las variables `LICENSE_*` (servidor, claves pineadas, gracia, JWKS) se
documentan en la sección **7. Sistema de Licenciamiento** más arriba.

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

**Última actualización:** 2026-07-13  
**Versión del documento:** 1.1 (agrega sección 7, Sistema de Licenciamiento)  
**Desarrollado con IA asistida**

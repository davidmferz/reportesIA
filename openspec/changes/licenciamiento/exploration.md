# Exploration: Sistema de Licenciamiento (client-side)

## Contexto / decisiones de negocio ya tomadas (no se re-discuten)
1. reportesIA se vende por instalación/cliente; cada cliente corre su propia instancia en su propio dominio. Mecánica de despliegue está fuera de alcance.
2. La emisión de licencias vive en un proyecto SEPARADO (license server del vendor). Este repo solo implementa el módulo CLIENTE (validación, enforcement, UI).
3. Enforcement: banner de warning durante N días de gracia, luego bloqueo total (solo queda accesible una pantalla de activación/licencia).
4. Constraints de licencia: fecha de validez (from/to) + máximo de usuarios activos. Sin feature flags ni cuotas de consumo en v1.

## Current State

El proyecto es Laravel 12 (PHP 8.2+) con Breeze (Blade + Alpine + Tailwind 3). No existe ningún concepto de licencia hoy: ni tabla, ni middleware, ni config. Puntos relevantes detectados:

- **Middleware**: registrado en `bootstrap/app.php` vía `$middleware->alias([...])`, patrón único hoy es `'admin' => EnsureUserIsAdmin::class` (`app/Http/Middleware/EnsureUserIsAdmin.php`), aplicado por grupo de rutas (`Route::middleware(['auth','admin'])->prefix('admin')...` en `routes/web.php`), NO global. No hay `withSchedule()` en `bootstrap/app.php` — el scheduler de Laravel NO está inicializado (routes/console.php solo tiene el comando `inspire` de ejemplo).
- **Rutas**: `routes/web.php` tiene: `/` (redirect), `/dashboard` (auth+verified), `/profile` (auth), grupo `admin` (auth+admin), y `require routes/auth.php` (Breeze: login/register/password, sin middleware auth — deben quedar exentas de bloqueo de licencia).
- **User model** (`app/Models/User.php`): campos `name,email,password,is_admin`; traits `HasFactory, Notifiable, LogsActivity`. **No existe campo `is_active`/`active`** en la tabla `users` — solo `is_admin`. No usa `SoftDeletes`.
- **Admin\UserController** (`app/Http/Controllers/Admin/UserController.php`): `store()`/`update()` crean/actualizan usuarios sin ningún chequeo de cupo. `destroy()` hace **hard delete** y además `ai_generations.user_id` tiene `onDelete('cascade')` (`database/migrations/2026_01_20_194230_create_ai_generations_table.php`) — borrar un usuario borra sus generaciones IA. Esto hace que "hard delete para liberar cupo" sea destructivo.
- **Precedente de flag `active`**: `App\Models\ForbiddenWord` ya tiene `protected $fillable=['word','reason','active']`, cast boolean, y `scopeActive()` (`app/Models/ForbiddenWord.php`). Mismo patrón se puede replicar en `User` (`is_active` + `scopeActive`) para "desactivar" un usuario sin destruir su historial ni sus generaciones — más seguro que hard-delete para liberar un cupo de licencia.
- **SoftDeletes**: sí se usa en `ReportType`, `ReportTypeFile`, `Chapter` — pero NO en `User`. No conviene añadir SoftDeletes a User solo para este feature; el toggle `is_active` cubre mejor el caso de uso ("liberar cupo sin perder historial/generaciones").
- **Config/env**: `.env.example` no tiene ninguna sección de licencia. Patrón existente: settings de servicios externos (ej. `openai-php/laravel`) van en `config/services.php` + `.env`. Un `config/license.php` (public key, server URL, grace days) seguiría el mismo patrón.
- **Cache**: `CACHE_STORE=database` (`config/cache.php` default `database`) — el caché comparte conexión con la DB de la app. **No es tamper-resistant**: un cliente con acceso admin puede correr `php artisan cache:clear` y borrar el estado de última validación/gracia. Recomendación: persistir el estado de licencia en una **tabla dedicada** (`license_states` o similar), no en el cache store, análogo a cómo `ActivityLog` es una tabla propia y no un log volátil.
- **Queue**: `QUEUE_CONNECTION=database`, y el script `composer dev` ya levanta `php artisan queue:listen` — infraestructura de queue existe y está en uso (para AI generation), reutilizable para revalidación en background, pero el scheduler (`withSchedule`) no está configurado — habría que agregarlo en `bootstrap/app.php`.
- **Criptografía disponible**: `php -m` confirma **`sodium`** y **`openssl`** cargados (PHP 8.2.30, ver `composer.json` `platform.php`). `sodium_crypto_sign_*` (Ed25519) está disponible sin dependencias nuevas — apto para verificar licencias firmadas offline sin round-trip al servidor central.
- **Auditoría**: `App\Traits\LogsActivity` (`app/Traits/LogsActivity.php`) audita automáticamente `created/updated/deleted` de cualquier modelo Eloquent vía boot hooks — apto para auditar cambios sobre el modelo de licencia (activación, renovación), pero eventos que NO son cambios de modelo (ej. "intento de validación falló", "entramos en modo bloqueo") no van a encajar en ese trait tal cual — necesitan un log explícito (`ActivityLog::create()` manual, patrón ya usado en otros controladores) o un log_name propio.
- **UI/Layouts**: dos layouts activos: `resources/views/layouts/app.blade.php` (Breeze clásico, usado en `/profile`) y `resources/views/components/layouts/crm.blade.php` (usado por casi todas las vistas admin y el dashboard, con dark mode vía Alpine `x-data="{darkMode}"` + Tailwind `dark:`). Un banner de licencia global necesita inyectarse en AMBOS layouts (o extraerse a un `<x-license-banner />` incluido en los dos) para cubrir todas las pantallas.
- **Testing**: Strict TDD activo (`composer.json` script `test` → `artisan config:clear && artisan test`). Tests existentes en `tests/Feature/AdminRouteScopingTest.php` muestran el patrón de crear admin vía factory + `actingAs()->get(route(...))->assertNotFound()/assertOk()`. Buen molde para tests de middleware de licencia (`assertRedirect` a pantalla de activación cuando bloqueado).
- **Comandos Artisan**: ya existe `app/Console/Commands/AuditTrainingExamplesCommand.php` como precedente de estructura de comando propio — sirve de molde para un futuro `license:check` (revalidación periódica) si se decide ese enfoque.

## Affected Areas
- `bootstrap/app.php` — registrar alias de middleware `license` (o similar) y, si se opta por revalidación periódica, `withSchedule()`.
- `app/Http/Middleware/` — nuevo `CheckLicense` (o `EnsureLicenseIsValid`), mismo estilo que `EnsureUserIsAdmin`.
- `routes/web.php` / `routes/auth.php` — exentar login, rutas de activación, y `/up` (health check ya existe vía `->health('/up')` en `bootstrap/app.php`) del middleware de licencia.
- `app/Models/User.php` + migración — posible campo `is_active` (+ `scopeActive`), siguiendo el precedente de `ForbiddenWord`.
- `app/Http/Controllers/Admin/UserController.php` — chequeo de cupo (`max_users`) en `store()` (y en el toggle de `is_active` si se agrega).
- Nuevo dominio `License` — modelo/tabla propia (estado de licencia: fechas, firma, último check, estado de gracia), controlador de activación, vistas (banner + pantalla de bloqueo/activación).
- `config/services.php` o nuevo `config/license.php` + `.env.example` — public key del vendor, URL del license server, días de gracia.
- `resources/views/layouts/app.blade.php` y `resources/views/components/layouts/crm.blade.php` — inyección del banner de warning.
- `app/Traits/LogsActivity.php` — reutilizable para auditar el modelo de licencia; eventos no ligados a un modelo (fallo de validación, entrada a modo bloqueo) necesitan logging manual vía `ActivityLog::create()`.
- `tests/Feature/` — nuevo `LicenseEnforcementTest.php` siguiendo el patrón de `AdminRouteScopingTest.php`.

## Approaches

### A. Contrato cliente↔servidor de licencias

1. **Pure phone-home (validación online en cada request/sesión)**
   - Pros: siempre al día, lógica simple, sin necesidad de criptografía local.
   - Cons: el cliente NO puede operar si el license server o su propia conexión a internet cae — inaceptable dado que cada cliente corre en su propio dominio/infra y el vendor no controla su uptime de red; acopla disponibilidad del producto del cliente a la disponibilidad del servidor central.
   - Effort: Low.

2. **Offline signed license (token firmado, sin revalidación periódica)**
   - Pros: funciona sin conexión, simple de implementar (`sodium_crypto_sign_verify_detached` contra clave pública del vendor embebida en `config/license.php`).
   - Cons: sin re-chequeo periódico, revocar/actualizar cupos o fechas de un cliente activo requiere reemitir y that el cliente reinstale el token manualmente; no hay forma de "empujar" un cambio (ej. cliente dejó de pagar) sin intervención humana en cada instalación.
   - Effort: Low-Medium.

3. **Híbrido: token firmado offline + revalidación periódica online con caché de gracia (RECOMENDADO)**
   - El license server emite un **token firmado** (JWS-like: payload JSON + firma Ed25519 vía `sodium`) con: `client_id`, `domain` (binding), `valid_from`, `valid_until`, `max_users`, `issued_at`.
   - El cliente valida la firma localmente en cada request (rápido, sin red) contra la public key embebida en config.
   - Periódicamente (Artisan command + `withSchedule()`, ej. diario) el cliente hace phone-home a un endpoint del license server para: (a) obtener un token renovado si cambió algo, (b) confirmar que la licencia sigue activa (revocación remota).
   - Si el phone-home falla (sin internet), el cliente sigue confiando en el último token firmado válido **hasta su `valid_until`** — no hay "grace por falta de red" adicional, la gracia de N días aplica solo a la EXPIRACIÓN de fechas/token, no a fallos de conectividad. Esto evita que perder internet temporalmente tumbe el sistema, sin necesitar lógica de gracia-por-offline separada.
   - Pros: resiliente a caídas de red del cliente, permite revocación/actualización remota del vendor, no depende 100% de uptime del license server, criptografía nativa (sin dependencias nuevas).
   - Cons: más piezas móviles (token + revalidación + almacenamiento de estado); requiere definir el contrato HTTP entre ambos proyectos (endpoint, formato del token, manejo de reloj/clock-tampering).
   - Effort: Medium.

**Domain binding**: el token debe incluir el dominio (`APP_URL` o el `Host` header en validación) para impedir copiar la licencia de una instalación a otra. Validar en cada request contra `config('app.url')` o `request()->getHost()`.

**Clock-tampering**: comparar `now()` contra `valid_until` es vulnerable a que el cliente atrase el reloj del servidor para extender la licencia indefinidamente. Mitigación mínima v1: guardar en la tabla dedicada de estado de licencia el **mayor timestamp de validación exitosa visto hasta ahora** (`last_seen_at`) y rechazar/forzar re-descarga si `now() < last_seen_at` (reloj retrocedido detectado). No es a prueba de balas pero cubre el caso trivial sin sobre-ingeniería para v1.

### B. Enforcement (banner → bloqueo)

1. **Middleware global + estado calculado on-the-fly en cada request**
   - Pros: simple, siempre coherente con la fuente de verdad (tabla dedicada).
   - Cons: overhead menor por request (una consulta liviana, cacheable en memoria de request).
   - Effort: Low.

2. **Middleware + revalidación solo periódica vía scheduler, con estado persistido leído en cada request**
   - El middleware SOLO lee el estado ya calculado (tabla `license_states`), nunca llama a la red ni recalcula la firma en cada request — la validación de firma+red ocurre en el job periódico. El middleware es una lectura barata de una fila.
   - Pros: separa "cuándo se valida" (barato, periódico) de "cuándo se enforce" (cada request, debe ser barato); evita hacer crypto verify en cada request.
   - Cons: hay una ventana entre que la licencia se invalida (ej. venció) y el job periódico corre. Se resuelve validando también la fecha `valid_until` localmente en el middleware (comparación de fechas es barata, no requiere red ni crypto) y dejando SOLO la verificación de firma/token/servidor para el job periódico.
   - Effort: Medium (RECOMENDADO, combinar con A.3: middleware chequea fechas+gracia desde estado persistido; job periódico refresca el token).

**Rutas exentas**: pantalla de activación/licencia, `routes/auth.php` (login — un cliente bloqueado igual debe poder loguearse para ver el estado, o se define que ni login funciona; **decisión pendiente para propuesta**: recomendado permitir login pero redirigir todo a la pantalla de licencia), `/up` (health check).

### C. Definición de "usuario activo" para el máximo

1. **Contar todas las filas de `users` (sin nuevo campo)**
   - Pros: cero cambios de schema.
   - Cons: para "liberar" un cupo hay que hacer hard-delete, que en este proyecto CASCADEa el borrado de `ai_generations` (pérdida de datos) — mal fit operacional.
   - Effort: Low.

2. **Agregar `is_active` boolean a `users` + `scopeActive` (RECOMENDADO)**
   - Sigue el precedente exacto de `ForbiddenWord::active` + `scopeActive()`. Permite al admin desactivar un usuario (libera cupo, el usuario no puede loguear) sin borrar su historial ni sus generaciones IA.
   - El chequeo de cupo en `Admin\UserController::store()` compara `User::active()->count()` (o `count()+1` al crear) contra `license->max_users`.
   - Cons: requiere migración + actualizar el controlador/vista de creación/edición de usuario + decidir si un usuario inactivo puede seguir logueado en sesiones existentes (recomendado: no — chequear `is_active` también en el login o en un middleware de sesión).
   - Effort: Medium.

## Recommendation

- **Contrato**: Opción A.3 (híbrido token firmado Ed25519 vía `sodium` + revalidación periódica). Es la única que sostiene el requisito implícito de "cada cliente en su propio dominio, con su propia disponibilidad de red" sin acoplar el producto del cliente al uptime del license server, y sin dependencias nuevas de PHP.
- **Enforcement**: Opción B.2 — middleware barato que lee estado persistido + compara fechas/gracia localmente; un job periódico (Artisan command + `withSchedule()`, a agregar en `bootstrap/app.php`) hace el phone-home real y la verificación de firma.
- **Max usuarios**: Opción C.2 — agregar `is_active` a `User` replicando el patrón de `ForbiddenWord`, y enforcement en `Admin\UserController::store()`.
- **Persistencia de estado de licencia**: tabla dedicada (no Laravel cache) para resistencia a tampering vía `cache:clear` y para tener auditoría consistente con el resto del proyecto (`ActivityLog`/`LogsActivity`).
- **Alcance del license server**: en esta propuesta solo se define el **contrato/API** (formato del token, endpoint de revalidación, formato de error/revocación). Scaffolding del proyecto del license server queda fuera de este repo y de este alcance — es explícitamente responsabilidad del otro proyecto.

## Risks
- Definir el contrato HTTP con un proyecto que aún no existe (license server) implica que la propuesta debe versionar el formato del token/endpoint para poder evolucionar sin romper clientes ya desplegados.
- El binding de dominio es débil si el vendor no controla el DNS del cliente (alguien podría clonar la instalación bajo el mismo dominio en otro hosting) — aceptable para v1 según alcance definido (mecánica de despliegue fuera de alcance).
- Mitigación de clock-tampering es parcial (solo detecta retroceso respecto al último check exitoso), no es una solución criptográfica robusta (requeriría un servidor de tiempo confiable) — debe documentarse como limitación conocida de v1.
- Bloquear TODAS las rutas salvo activación es agresivo si el login también queda exento o no — necesita decisión explícita en la propuesta (login exento vs. bloqueado también).
- Agregar `is_active` a `User` es un cambio de schema que toca el flujo de login (verificar en `Auth` o vía middleware) — si no se excluye correctamente a sesiones ya iniciadas, un usuario desactivado podría seguir operando hasta que expire su sesión.

## Ready for Proposal
**Yes.** Hay contexto suficiente del codebase (middleware, modelos, config, cache, queue/scheduler, crypto disponible, precedentes de flags/auditoría/tests) para escribir `proposal.md`. Puntos que la propuesta debe resolver explícitamente (no bloquean redactarla, pero deben quedar decididos ahí):
1. ¿Login queda exento del bloqueo por licencia, o también se bloquea?
2. Formato exacto del token de licencia y del endpoint de revalidación (contrato con el license server).
3. Nombre y ubicación exactos de la tabla de estado de licencia y del modelo (`License`, `LicenseState`, etc.).
4. Frecuencia del job periódico de revalidación (diario, cada N horas).

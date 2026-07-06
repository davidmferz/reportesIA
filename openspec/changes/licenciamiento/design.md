# Design: Sistema de Licenciamiento (client-side)

## Technical Approach

Módulo cliente con separación estricta: **verificación cara** (firma Ed25519 + red) solo en el job periódico `license:check`; **enforcement barato** (lectura de una fila + comparación de fechas) en cada request. Fuente de verdad única: fila única en `license_states`. Alinea con proposal A.3 + B.2 + C.2. Sin dependencias nuevas (`sodium` ya cargado).

## Token Format

Formato **detached, JWS-like propio** (no JWT):

```
license_token = base64url(json(payload)) + "." + base64url(sig)
sig = sodium_crypto_sign_detached(json(payload), vendor_secret_key)   // solo en el server
payload = {"client_id","domain","valid_from","valid_until","max_users","issued_at","schema_version"}
```

Fechas ISO-8601 UTC; `schema_version` = int (v1). Verificación cliente: `sodium_crypto_sign_verify_detached(sig, json_bytes, public_key)`.

| Decisión | Alternativa | Rationale |
|---|---|---|
| Detached Ed25519 + JSON | `firebase/php-jwt` / lcobucci JWT | `sodium` es nativo → cero deps; evita clase de bugs `alg=none`/confusión de algoritmo de JWT; controlamos ambos extremos, no necesitamos interop con terceros. |
| Public key en `config/license.php` desde `env('LICENSE_PUBLIC_KEY')` (base64) | Key hardcodeada en código | Config cacheable + rota sin re-deploy de código. **Private key JAMÁS en este repo** (vive en el license server del vendor). |

**Regla firma:** se firma exactamente el mismo string base64url(payload) que viaja; el cliente re-decodifica y verifica sobre esos bytes crudos (no re-serializa) para evitar drift de orden de claves.

## Verification Layer — `app/Services/LicenseVerifier.php`

(proposal lo nombra `LicenseVerifier`; mantengo ese nombre — es el "LicenseService" del brief.)

```php
verify(string $token): array          // decodifica+verifica firma; lanza LicenseInvalidException si falla
resolveStatus(?LicenseState $s): LicenseStatus  // enum: Valid|Grace|Blocked|Unlicensed
activate(string $token, User $by): LicenseState // verify + domain-bind + persist + audit
revalidate(LicenseState $s): void     // HTTP phone-home (usado por el command)
```

`LicenseStatus` (enum PHP):

| Estado | Condición (evaluada por `resolveStatus`, barata, sin red/crypto) |
|---|---|
| `Unlicensed` | no hay fila / sin `raw_token` |
| `Valid` | `now ≤ valid_until` y `last_check_result ≠ revoked` y no rollback |
| `Grace` | `valid_until < now ≤ valid_until + grace_days` |
| `Blocked` | `now > valid_until + grace_days` **o** `revoked` **o** `now < last_seen_at` (rollback) |

Domain-bind: comparar `payload.domain` contra `config('app.url')` (host), **no** `request()->getHost()` (Host header spoofable). El middleware NO llama a `verify()` (crypto) por request — solo `resolveStatus()`.

## Persistence — `license_states` + `LicenseState`

| Campo | Tipo | Uso |
|---|---|---|
| `raw_token` | text | token crudo activo |
| `payload` | json | cache decodificado (client_id, domain, max_users, fechas) |
| `valid_from` / `valid_until` | datetime | comparación de fechas del middleware |
| `max_users` | int | cupo |
| `last_check_at` | datetime nullable | último phone-home |
| `last_check_result` | string | `ok` \| `unreachable` \| `revoked` \| `invalid` |
| `last_seen_at` | datetime | high-water mark anti-rollback |
| `activated_at` / `activated_by` | datetime / FK users nullable | auditoría de activación |

Modelo `LicenseState` con `use LogsActivity`, cast `payload`→array, `protected $activityLogExclude=['raw_token']`, y helper estático `current(): ?self` (fila única). created/updated se auditan solos vía el trait.

## Middleware — `EnsureLicenseIsValid` (alias `license`)

Registro en `bootstrap/app.php`: agregar `'license' => \App\Http\Middleware\EnsureLicenseIsValid::class` al array `alias([])` (precedente exacto de `admin`).

**Estrategia de exención (whitelist por composición, no por skip-list global):** el alias `license` se agrega a los grupos autenticados (`dashboard`, grupo `profile`, grupo `admin`). Rutas de activación llevan `auth` pero **NO** `license`. `routes/auth.php` (login/register/password) y `/up` (health) quedan exentas por no tener el middleware — más robusto que una lista negra global que hay que mantener sincronizada.

Lógica:
- `Blocked` → `redirect()->route('license.activation.show')` (salvo que ya esté ahí).
- `Grace` → `View::share('licenseGraceUntil', ...)` y continúa.
- `Valid`/`Unlicensed(valid)` → continúa.

**Banner: `View::share` desde el middleware, NO view composer.** Rationale: el middleware ya cargó `LicenseState::current()` y computó el estado; un view composer re-consultaría la fila de forma independiente, duplicando el read. `View::share` reusa el estado ya computado (single source, cero query extra). El `<x-license-banner>` incluido en `layouts/app.blade.php` y `components/layouts/crm.blade.php` solo lee la var compartida.

## Scheduled Revalidation — `license:check`

Command `app/Console/Commands/LicenseCheckCommand.php` (molde: `AuditTrainingExamplesCommand`). Scheduler: agregar `->withSchedule(fn(Schedule $s) => $s->command('license:check')->daily())` en `bootstrap/app.php` (hoy no existe).

**Contrato HTTP** `POST {server}/api/v1/licenses/{client_id}/validate`:

```jsonc
// request
{ "domain": "cliente.com", "schema_version": 1, "current_token": "<raw>" }
// response 200
{ "status": "active"|"revoked", "token": "<nuevo raw|null>", "reason": null|"..." }
```

`Http::timeout(10)->connectTimeout(5)->retry(1, 500)`. Política:
- **Red caída / 5xx** → `last_check_result='unreachable'`, **NO** cambia estado (offline tolerance: se confía en el token local hasta su `valid_until`).
- **`active` + token nuevo** → `verify()` el token, reemplaza `raw_token`+`payload`, sube `last_seen_at=max(last_seen_at, now)`.
- **`revoked`** → `last_check_result='revoked'` (fuerza `Blocked` en `resolveStatus`) + `ActivityLog` manual `revoke`.

## User Capacity

- Middleware `EnsureUserIsActive` (append a grupos autenticados): si `!user->is_active` → `Auth::logout()` + redirect login con mensaje (expulsa en el siguiente request, proposal #6). Chequeo por request; sesión existente no sobrevive.
- `users.is_active` boolean default `true` (migración), `scopeActive` en `User` (patrón `ForbiddenWord`).
- Cupo en `Admin\UserController`: `store()` rechaza si `User::active()->count() >= LicenseState::current()?->max_users`; toggle `is_active` false→true (nuevo método `toggleActive`) aplica el mismo chequeo. `max_users` leído de `LicenseState::current()`.

## Activation Flow

`LicenseController` (`auth`+`admin`, **sin** `license`): `show` (form pegar token) + `store` (`LicenseVerifier::activate`). Solo admin. Vista `resources/views/license/activation.blade.php` (layout `crm`). Auditoría: activate/renew → auto vía `LogsActivity` del modelo; `expire`/`block`/`revoke` (no son cambios de modelo) → `ActivityLog::create(['log_name'=>'License', 'event'=>..., ...])` manual, patrón ya usado en el proyecto.

## Testing Strategy (Strict TDD, sin coverage driver)

| Layer | Qué | Cómo |
|---|---|---|
| Unit | firma/estados | `sodium_crypto_sign_keypair()` en test; helper `makeToken(array $payload, $secret)`; `config()->set('license.public_key', base64(pk))`; asertar `resolveStatus` por cada estado |
| Feature | enforcement/rutas | patrón `AdminRouteScopingTest`: `actingAs()->get()->assertRedirect(route('license.activation.show'))` bloqueado; login OK bajo bloqueo; banner en gracia |
| Feature | revalidación | `Http::fake([...])` → active/revoked/timeout; asertar transición de estado |
| Feature | expiry/gracia/rollback | `$this->travelTo()` para cruzar `valid_until` y `+grace_days`; setear `last_seen_at` futuro para rollback |
| Cupo | max_users | crear N=max activos, asertar `store()` N+1 rechazado |

No `--coverage` (sin Xdebug/PCOV) — se valida por aserciones de comportamiento, no por %.

## Threat Model v1

**Protege contra:** copia casual a otro dominio (domain-bind), uso vencido (fechas+gracia), corte remoto (phone-home `revoked`).
**NO protege contra (riesgo aceptado, documentado):** **parcheo de código fuente** — el cliente hostea el PHP y tiene admin/DB; puede comentar el middleware, reemplazar la public key, o `UPDATE license_states`. El enforcement es **disuasivo/contractual, no una barrera de seguridad criptográfica.** Clock-tampering solo detecta retroceso (`last_seen_at`), no adelanto, y ese campo vive en la DB que el cliente controla.

## Migration / Rollout

`license_states` e `is_active` (default `true`, no altera comportamiento previo) son `down()`-ables. Sin middleware registrado, config/vistas/modelo son inertes. Rollback = quitar alias `license` + `withSchedule()`.

## Open Questions

- [ ] ¿El license server responde el token renovado firmado, o solo status? (asumido: token completo firmado — el cliente re-verifica firma).
- [ ] ¿`client_id` viaja en URL y en payload deben coincidir? (asumido: sí, el middleware/command lo cruza).

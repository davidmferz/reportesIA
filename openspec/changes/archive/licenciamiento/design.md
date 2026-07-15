# Design: Sistema de Licenciamiento (client-side)

## Technical Approach

Módulo cliente con separación estricta: **verificación cara** (firma EdDSA +
red) solo en el job periódico `license:check`; **enforcement barato** (lectura
de una fila + comparación de fechas) en cada request. Fuente de verdad única:
fila única en `license_states`. Alinea con proposal A.3 + B.2 + C.2.

## Token Format — JWS / EdDSA

> Ver `reconciliation-jws.md` para el porqué del pivot (este documento ya
> describe el resultado final, no el intermedio).

El token es un **JWS estándar en serialización compacta**, `alg=EdDSA`
(Ed25519), verificado con `firebase/php-jwt` (`^7.1`, `composer.json`) — no
hay crypto manejada a mano en el cliente.

```
license_token = base64url(header) + "." + base64url(payload) + "." + base64url(sig)
header  = {"alg": "EdDSA", "kid": "<key id>", ...}
payload = {"client_id","domain","valid_from","valid_until","max_users",
           "issued_at","schema_version","kid","iss","exp"}
```

Claims requeridos por el cliente (`LicenseVerifier::REQUIRED_CLAIMS`):
`client_id`, `domain`, `valid_from`, `valid_until`, `max_users`,
`issued_at`, `schema_version`, `kid`.

| Decisión | Alternativa | Rationale |
|---|---|---|
| JWS/EdDSA vía `firebase/php-jwt` | Ed25519 detached + JSON propio (`sodium_crypto_sign_*` a mano) | El firmante es el licensing-server (Java/WildFly) y los clientes son heterogéneos. Un JWS estándar elimina el riesgo de canonicalización cross-language (Java y PHP serializando el mismo JSON byte-a-byte distinto) y trae `kid`/rotación + librerías maduras en todo stack. Ver "Superseded approach" abajo. |
| Clave pública seleccionada por `kid` (header, no un único valor fijo) | Una sola clave pineada sin rotación | Permite rotar del lado del servidor (JWKS) sin romper tokens ya emitidos con el `kid` anterior, y sin requerir redeploy del cliente para el caso común. |
| Claves pineadas en `config/license.php` ganan sobre JWKS de red por defecto | JWKS de red siempre gana | Evita que una respuesta de red comprometida (MITM, DNS spoof) pueda introducir una clave de verificación no confiada; el opt-in explícito (`trust_network_jwks=true`) es responsabilidad del operador. |

**Regla de verificación:** `JWT::decode()` fuerza el algoritmo asociado a la
`Key` resuelta (EdDSA) — así se rechazan de raíz `alg:none` y la confusión de
algoritmo (p. ej. HS256 firmado con la clave pública tratada como secreto
HMAC). El `kid` se extrae del header **sin verificar la firma todavía**
(`LicenseVerifier::extractKid()`), solo para saber qué clave probar; cualquier
problema de forma en esa extracción se trata como "no hay kid" y falla limpio
más abajo con `LicenseInvalidException`, nunca con una excepción no
capturada.

## Verification Layer — `app/Services/LicenseVerifier.php`

```php
verify(string $token): array          // decodifica+verifica firma (JWS/EdDSA); lanza LicenseInvalidException si falla
resolveStatus(?LicenseState $s): LicenseStatus  // enum: Valid|Grace|Blocked|Unlicensed
activate(string $token, User $by): LicenseState // verify + domain-bind + persist + audit
```

(la revalidación HTTP vive en `LicenseCheckCommand`, no en el servicio — ver
más abajo.)

`LicenseStatus` (enum PHP, backed string):

| Estado | Condición (evaluada por `resolveStatus`, barata, sin red/crypto) |
|---|---|
| `Unlicensed` | no hay fila `LicenseState::current()` |
| `Valid` | `now ≤ valid_until` y `last_check_result ≠ revoked` y no hay rollback |
| `Grace` | `valid_until < now ≤ valid_until + grace_days` |
| `Blocked` | `now > valid_until + grace_days` **o** `last_check_result = revoked` **o** `now < last_seen_at` (rollback de reloj) |

Domain-bind: `LicenseVerifier::expectedDomain()` compara `claims['domain']`
contra el host de `config('app.url')` (nunca contra `request()->getHost()`,
que es un Host header spoofable). El middleware **NO llama a `verify()`**
(crypto) por request — solo a `resolveStatus()`, que es puramente lectura de
fila + comparación de fechas.

## Public Key Resolution by `kid` (JWKS)

Añadido junto con el pivot a JWS — no existía en el diseño original de un
único valor pineado:

- **Keyset local** (`config('license.keys')`): hasta dos pares `kid => clave
  pública base64` desde `LICENSE_KID`/`LICENSE_PUBLIC_KEY` y
  `LICENSE_KID_2`/`LICENSE_PUBLIC_KEY_2` (dos ranuras para que la clave del
  licensing-server y la del helper de desarrollo `make-dev-token.php`
  convivan sin pisarse — `config/license.php`).
- **JWKS de red** (`LicenseVerifier::jwksKeySet()`): `GET
  {jwks_url|server_url/api/v1/products/{product_id}/jwks}`, parseado con
  `Firebase\JWT\JWK::parseKeySet($jwks, 'EdDSA')`, cacheado
  `jwks_cache_hours` (default 24h) vía `Cache::put`. Cualquier error de red,
  HTTP no-2xx o JSON con forma inesperada devuelve un keyset vacío
  (`fetchJwks()`/`jwksKeySet()` fail-safe: nunca propagan la excepción hacia
  `verify()`).
- **Precedencia** (`LicenseVerifier::resolveKey()`): si el `kid` está pineado
  localmente, **gana sobre la JWKS de red** salvo opt-in explícito
  (`license.trust_network_jwks=true`). Si el `kid` no está pineado, se
  resuelve contra la JWKS (así rota el server sin redeploy del cliente para
  el caso común de rotación).

## Persistence — `license_states` + `LicenseState`

| Campo | Tipo | Uso |
|---|---|---|
| `raw_token` | text | JWS crudo activo (compact serialization) |
| `payload` | json | claims decodificados (client_id, domain, max_users, fechas, kid, schema_version) |
| `valid_from` / `valid_until` | datetime | comparación de fechas del middleware |
| `max_users` | int | cupo |
| `last_check_at` | datetime nullable | último phone-home |
| `last_check_result` | string | `active` \| `unreachable` \| `revoked` \| ... |
| `enforcement_status` | string (cast a `LicenseStatus`) | último status resuelto, usado por `EnsureLicenseIsValid::recordTransition()` para auditar solo en el cambio |
| `last_seen_at` | datetime | high-water mark anti-rollback |
| `activated_at` / `activated_by` | datetime / FK users nullable | auditoría de activación |

Modelo `LicenseState` (`app/Models/LicenseState.php`) con `use LogsActivity`,
cast `payload`→`array`, `enforcement_status`→`LicenseStatus`,
`activityLogExclude = ['raw_token']` (el JWS crudo nunca se vuelca al audit
log), y helper estático `current(): ?self` (última fila por `id`, modelo de
fila única — `activate()` hace `LicenseState::query()->delete()` antes de
crear la nueva).

## Middleware — `EnsureLicenseIsValid` (alias `license`)

Registrado en `bootstrap/app.php` (`alias(['license' =>
\App\Http\Middleware\EnsureLicenseIsValid::class, ...])`), junto a `admin` y
`active` (alias de `EnsureUserIsActive`).

**Estrategia de exención (whitelist por composición, no por skip-list
global):** el alias `license` se agrega a los grupos autenticados
(`dashboard`, `profile`, `admin`, `routes/web.php`). Las rutas de activación
llevan `auth`+`admin` pero **NO** `license`; la ruta de bloqueo lleva solo
`auth`. `routes/auth.php` (login/register/password) y `/up` (health) quedan
exentas por no tener el middleware en absoluto.

Lógica (`EnsureLicenseIsValid::handle()`):
- `Valid` → `$next($request)`.
- `Grace` → `passWithBanner()`: comparte `licenseGraceUntil` vía
  `View::share` y continúa.
- `Blocked` / `Unlicensed` → `block()`.

**Bloqueo consciente del rol** (gap cerrado respecto al diseño original, ver
`reconciliation-jws.md`): `block()` redirige al **admin** a
`license.activation.show`; a un usuario **no-admin** lo redirige a
`license.blocked.show`, una pantalla read-only "contactá a tu
administrador" (200, no el 403 que hubiera dado la pantalla admin-only).

**Banner: `View::share` desde el middleware, NO view composer.** El
middleware ya cargó `LicenseState::current()` y computó el estado; un view
composer re-consultaría la fila de forma independiente, duplicando el read.
`View::share` reusa el estado ya computado (single source, cero query extra).

**Auditoría de transición** (`recordTransition()`): compara el
`enforcement_status` persistido contra el nuevo status resuelto; solo si
cambia y el nuevo es `Grace` o `Blocked` crea un `ActivityLog` (`log_name =>
'LicenseState'`, `event => 'license_transition'`), y persiste el nuevo status
con `saveQuietly()` (no duplica el log vía `LogsActivity`).

## Scheduled Revalidation — `license:check`

`app/Console/Commands/LicenseCheckCommand.php`. Scheduler:
`bootstrap/app.php` → `->withSchedule(fn (Schedule $s) =>
$s->command('license:check')->daily())`.

**Contrato HTTP** `POST {server_url}/api/v1/licenses/{client_id}/validate`:

```jsonc
// request
{ "domain": "cliente.com", "schema_version": 1, "current_token": "<raw JWS>" }
// response 200
{ "status": "active"|"revoked", "token": "<nuevo JWS|null>", "reason": null|"..." }
```

`Http::timeout(10)->connectTimeout(5)->retry(1, 500)`. Política
(`LicenseCheckCommand::handle()`):
- Si `state === null` (nunca activada) o falta `server_url`/`client_id` en
  config → se omite el phone-home, `SUCCESS`.
- **Excepción de red / no-2xx** → se mantiene el estado local intacto,
  `SUCCESS` (offline tolerance: se confía en el token local hasta su
  `valid_until` + gracia).
- **`status: revoked`** → `last_check_result = 'revoked'` (fuerza `Blocked`
  en `resolveStatus`), persistido con `save()` (no `saveQuietly()`: la
  revocación se audita vía `LogsActivity`).
- **`status: active`, sin `token` nuevo** → confirma vigencia: actualiza
  `last_check_at`/`last_check_result`/`last_seen_at`, no toca `raw_token`.
- **`status: active`, con `token` nuevo** → el token renovado se
  **re-verifica localmente** con `LicenseVerifier::verify()` (firma, `kid`,
  dominio, claims) antes de reemplazar el estado persistido — nunca se
  confía ciegamente en lo que devuelve la red. Si la verificación local
  falla, `FAILURE` y el estado local no se toca.
- **`status` inesperado/ausente** → warning, estado local intacto, `SUCCESS`.

## User Capacity

- Middleware `EnsureUserIsActive` (alias `active`, agregado a los mismos
  grupos autenticados que `license`): si `!$user->is_active` →
  `Auth::guard('web')->logout()` + invalida sesión + redirect a `login`.
  Expulsión en el siguiente request; una sesión ya iniciada no sobrevive al
  siguiente hit.
- `users.is_active` boolean, default `true` (migración, no altera
  comportamiento previo). `User::scopeActive()` (`where('is_active', true)`).
- Cupo en `Admin\UserController::assertUserCapacityAvailable()`: rechaza
  (`ValidationException` sobre `max_users`) si
  `LicenseState::current()?->max_users` es `null` **o**
  `User::active()->count() >= $maxUsers` (guard explícito, no confía en el
  cast implícito null→bool de PHP). Se llama desde `store()` (creación) y
  desde `toggleActive()` cuando reactiva (`false → true`).
- `toggleActive()`: **desactivar SIEMPRE está permitido** (solo libera cupo,
  nunca lo consume) — es un `$user->update(['is_active' => false])`, nunca
  un `delete()`. La fila del usuario y todo lo que le pertenece (incluidas
  sus `ai_generations`, con `onDelete('cascade')` en la FK) sobrevive
  intacta; la cascada de borrado solo se dispararía ante un `DELETE` real de
  la fila `users`, que este flujo nunca ejecuta.

## Activation Flow

`LicenseController` (`app/Http/Controllers/LicenseController.php`):
- `show()` (`auth`+`admin`, **sin** `license`): pantalla de activación,
  muestra `LicenseState::current()` si existe.
- `store()` (`auth`+`admin`, **sin** `license`): `LicenseVerifier::activate()`
  sobre el token pegado; si `LicenseInvalidException`, redirige de vuelta con
  error en `token` y **no** toca la persistencia.
- `blocked()` (solo `auth`, sin `admin`): pantalla read-only para no-admin
  bloqueado.

Auditoría: activar/renovar la licencia pasa por el modelo `LicenseState`
(`LogsActivity` automático); transiciones que no son cambios de modelo
(`license_transition`, revocación) usan `ActivityLog::create()` manual, el
mismo patrón ya usado en el resto del proyecto.

## Testing Strategy (Strict TDD, sin coverage driver)

| Layer | Qué | Cómo |
|---|---|---|
| Unit | firma/estados JWS | par de claves EdDSA generado en test; helper de fixture que firma un JWS con `kid`; `config()->set('license.keys', [...])`; asertar `resolveStatus`/`verify` por cada caso (válido, `alg:none`, HS256, `kid` desconocido, claim faltante, dominio distinto) |
| Unit | resolución de clave por `kid` | pineada vs. JWKS de red, precedencia y opt-in `trust_network_jwks` (`LicenseKeyResolutionTest`) |
| Feature | enforcement/rutas | patrón `AdminRouteScopingTest`: `actingAs()->get()->assertRedirect(...)` bloqueado (admin → activación, no-admin → pantalla de contacto); login OK bajo bloqueo; banner en gracia |
| Feature | revalidación | `Http::fake([...])` → `active`/`revoked`/timeout; asertar transición de estado (`LicenseCheckCommandTest`) |
| Feature | expiry/gracia/rollback | `$this->travelTo()` para cruzar `valid_until` y `+grace_days`; setear `last_seen_at` futuro para rollback (`LicenseRollbackTest`, `LicenseStatusTest`) |
| Feature | cupo | crear/reactivar N=max usuarios activos, asertar el N+1 rechazado; desactivar nunca rechazado (`UserCapacityTest`) |

No `--coverage` (sin Xdebug/PCOV) — se valida por aserciones de
comportamiento, no por %.

## Threat Model v1

**Protege contra:** copia casual a otro dominio (domain-bind), uso vencido
(fechas+gracia), corte remoto (phone-home `revoked`), confusión de algoritmo
o `alg:none` (forzado por `firebase/php-jwt` vía la `Key` tipada), clave de
verificación no confiable inyectada por red (precedencia de clave pineada
sobre JWKS por defecto).

**NO protege contra (riesgo aceptado, documentado):** **parcheo de código
fuente** — el cliente hostea el PHP y tiene admin/DB; puede comentar el
middleware, reemplazar la clave pública pineada, o `UPDATE license_states`
directamente. El enforcement es **disuasivo/contractual, no una barrera de
seguridad criptográfica.** Clock-tampering solo detecta retroceso
(`last_seen_at`), no adelanto, y ese campo vive en la DB que el cliente
controla.

## Migration / Rollout

`license_states` e `is_active` (default `true`, no altera comportamiento
previo) son `down()`-ables. Sin el alias `license` registrado, config/vistas/
modelo son inertes. Rollback = quitar los alias `license`/`active` +
`withSchedule()` de `bootstrap/app.php`.

## Superseded approach: Ed25519 detached + JSON propio

La primera versión de este diseño (antes de que existiera el
licensing-server) especificaba un formato **detached, JWS-like propio, no
JWT/JWS estándar**:

```
license_token = base64url(json(payload)) + "." + base64url(sig)
sig = sodium_crypto_sign_detached(json(payload), vendor_secret_key)
```

verificado a mano con `sodium_crypto_sign_verify_detached()` (sin
dependencias nuevas, `sodium` ya cargado) y una única clave pública pineada
en `config/license.php`, sin `kid` ni JWKS.

**Por qué se abandonó:** al construirse un licensing-server independiente
(Java 21/WildFly, Change A) que emite las licencias de toda la cartera de
clientes, el firmante dejó de ser PHP y los clientes pasaron a ser
heterogéneos. Un formato canónico propio corre el riesgo real de que Java y
PHP serialicen el mismo JSON byte-a-byte distinto (canonicalización
cross-language), lo que rompería la verificación de forma silenciosa y
específica del stack. Se pivotó a **JWS estándar con `alg=EdDSA`**
(`firebase/php-jwt` en el cliente) porque: (1) elimina el problema de
canonicalización — el JWS especifica la codificación byte a byte; (2) trae
`kid`/rotación de clave sin diseño ad-hoc; (3) usa librerías maduras y
auditadas en ambos extremos en vez de crypto hecha a mano. El detalle
completo del pivot y la tabla de deltas concretos vive en
`reconciliation-jws.md`. El **threat model y la arquitectura del cliente
(offline-first, gracia, rollback, `license_states`, middleware) no
cambiaron** — solo el formato del token y la resolución de clave.

## Open Questions

- [x] ¿El license server responde el token renovado firmado, o solo status?
      → Resuelto: token completo firmado (JWS); el cliente re-verifica firma
      con `LicenseVerifier::verify()` antes de reemplazar el estado
      persistido (`LicenseCheckCommand::handleActive()`).
- [x] ¿`client_id` viaja en URL y en payload deben coincidir? → El
      `client_id` va en la URL del phone-home
      (`/api/v1/licenses/{client_id}/validate`); el payload trae su propio
      `client_id` como claim requerido, pero el cliente no cruza
      explícitamente ambos valores hoy — riesgo aceptado de bajo impacto
      (mismo threat model: parcheo de código ya es el vector no cubierto).

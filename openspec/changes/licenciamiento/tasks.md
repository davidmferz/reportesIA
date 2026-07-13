# Tasks: Sistema de Licenciamiento

Strict TDD: GREEN follows RED.

## Batch 1 — Foundation

- [x] 1.1 `config/license.php` (public keys by `kid` and/or `jwks_url`, server_url, revalidation_hours=24, grace_days=14, client_id, product_id) + `.env.example` keys. **JWS/EdDSA** (reconciled with licensing server — see `reconciliation-jws.md`).
- [x] 1.2 RED `tests/Unit/LicenseStateTest.php` — schema + `LicenseState::current()` + `payload` cast.
- [x] 1.3 GREEN migration `create_license_states_table` (raw_token, payload json, valid_from, valid_until, max_users, last_check_at, last_check_result, last_seen_at, activated_at, activated_by FK) + `app/Models/LicenseState.php` (`LogsActivity`, `activityLogExclude=['raw_token']`, `current()`).
- [x] 1.4 RED `tests/Unit/UserIsActiveTest.php` — default `is_active=true`; `scopeActive()` excludes inactive.
- [x] 1.5 GREEN migration `add_is_active_to_users_table` + `User` fillable/cast/`scopeActive()` (mirror `ForbiddenWord`).

## Batch 2 — LicenseVerifier

- [x] 2.1 RED `tests/Unit/LicenseVerifierTest.php::verify` — valid **JWS/EdDSA** accepted; tampered/wrong-key/malformed rejected; **`alg:none`/algorithm-confusion rejected**; unknown `kid` rejected (generate Ed25519 keypair + `makeJws()` helper).
- [x] 2.2 GREEN `app/Services/LicenseVerifier.php::verify()` — parse JWS, enforce `alg=EdDSA`, select public key by header `kid`, verify signature, claim validation (`client_id,domain,valid_from,valid_until,max_users,issued_at,schema_version,kid`), `LicenseInvalidException`. Uses a JWS lib (`web-token/jwt-*` or EdDSA-capable JWT), NOT ad-hoc detached handling.
- [x] 2.3 RED extend — domain mismatch (`domain` claim ≠ host) rejected.
- [x] 2.4 GREEN domain-bind check in `verify()`.
- [x] 2.9 RED `tests/Unit/LicenseKeyResolutionTest.php` — public key selected by `kid`; rotated `kid` verifies when key present; unknown `kid` fails.
- [x] 2.10 GREEN key resolution by `kid` from `config/license.php` keyset and/or cached JWKS (`GET {server}/api/v1/products/{product_id}/jwks`); network JWKS does not override a pinned key unless opted in.
- [x] 2.5 RED `tests/Unit/LicenseStatusTest.php` — `resolveStatus()` covers Unlicensed/Valid/Grace/Blocked+rollback.
- [x] 2.6 GREEN `app/Enums/LicenseStatus.php` + `LicenseVerifier::resolveStatus()`.
- [x] 2.7 RED `tests/Feature/LicenseActivationTest.php` — valid token persists + audits; invalid rejects, no change.
- [x] 2.8 GREEN `LicenseVerifier::activate(string $token, User $by)`.

## Batch 3 — Middleware + registration

- [x] 3.1 RED `tests/Feature/LicenseEnforcementTest.php` — valid passes; grace banners; grace-exhausted redirects; `/up`+`routes/auth.php` exempt.
- [x] 3.2 GREEN `app/Http/Middleware/EnsureLicenseIsValid.php` — `resolveStatus()` only, `View::share('licenseGraceUntil')`, redirect `license.activation.show`.
- [x] 3.3 RED extend — grace→blocked audited once, not per-request.
- [x] 3.4 GREEN transition-audit logic in middleware.
- [x] 3.5 RED `tests/Feature/UserActiveEnforcementTest.php` — deactivated session evicted immediately; active unaffected.
- [x] 3.6 GREEN `app/Http/Middleware/EnsureUserIsActive.php`.
- [x] 3.7 GREEN register both aliases in `bootstrap/app.php` (mirror `admin`), append to dashboard/profile/admin groups.
- [x] 3.8 RED extend `LicenseEnforcementTest` — blocked **non-admin** user is sent to the "contact your administrator" screen (HTTP 200), NOT the admin-only activation screen and NOT a 403; blocked **admin** still redirected to activation.
- [x] 3.9 GREEN role-aware block target in `EnsureLicenseIsValid`: admin → `license.activation.show`; non-admin → `license.blocked.show`.

## Batch 4 — license:check + scheduler + contract

- [x] 4.1 RED `tests/Feature/LicenseCheckCommandTest.php` — `Http::fake()`: renewed replaces state; `revoked` invalidates+audits; unreachable state unchanged.
- [x] 4.2 GREEN `app/Console/Commands/LicenseCheckCommand.php` (mirror `AuditTrainingExamplesCommand`) — `POST {server}/api/v1/licenses/{client_id}/validate`, `Http::timeout(10)->connectTimeout(5)->retry(1,500)`, re-verifies, bumps `last_seen_at`.
- [x] 4.3 GREEN `->withSchedule(fn($s)=>$s->command('license:check')->daily())` in `bootstrap/app.php`.
- [x] 4.4 RED `tests/Unit/LicenseRollbackTest.php` — `now() < last_seen_at` forces revalidation. **Sin RED genuino:** la detección ya existía desde 2.6; el test se agregó igual como red de seguridad en vez de debilitar la lógica para forzar un rojo artificial.
- [x] 4.5 GREEN rollback detection in `resolveStatus()` (`LicenseVerifier.php:119`).

## Batch 5 — Activation screen

- [x] 5.1 RED `tests/Feature/LicenseControllerTest.php` — `show` (auth+admin, no `license` mw); `store` valid activates; invalid errors, no change.
- [x] 5.2 GREEN `app/Http/Controllers/LicenseController.php` + `routes/web.php` (`license.activation.show/store`, auth+admin only).
- [x] 5.3 GREEN view `resources/views/license/activation.blade.php` (layout `crm`).
- [x] 5.4 RED `tests/Feature/LicenseBlockedScreenTest.php` — `license.blocked.show` reachable by authenticated non-admin (auth, NO `admin`, NO `license` mw to avoid a redirect loop); shows contact-admin message; not reachable when unauthenticated.
- [x] 5.5 GREEN `LicenseController::blocked()` + route `license.blocked.show` (auth only) + view `resources/views/license/blocked.blade.php` (read-only "contactá a tu administrador", layout `crm`).

## Batch 6 — UI banner

- [x] 6.1 RED `tests/Feature/LicenseBannerTest.php` — renders when `licenseGraceUntil` shared, absent otherwise (test real contra `/dashboard`, atravesando el middleware; no se fakea la variable de vista).
- [x] 6.2 GREEN `resources/views/components/license-banner.blade.php`.
- [x] 6.3 GREEN include `<x-license-banner />` in `layouts/app.blade.php` + `components/layouts/crm.blade.php`.

## Batch 7 — User capacity (Admin\UserController)

- [x] 7.1 RED `tests/Feature/Admin/UserCapacityTest.php` — under cap ok; at cap rejects (create + reactivate); desactivar siempre permitido; sin licencia no hay cupo.
- [x] 7.2 GREEN `Admin\UserController::store()` rejects via `assertUserCapacityAvailable()`. **Nota:** la guarda es explícita (`$maxUsers !== null && count < $maxUsers`), NO la comparación `>= null` de la task original — esa dependía del casteo implícito a bool de PHP, que funciona por accidente del lenguaje y no expresa la regla de negocio.
- [x] 7.3 GREEN `Admin\UserController::toggleActive()` applies same cap check; route `admin.users.toggle-active` added.

## Batch 8 — Integration, docs, dev tooling

- [x] 8.1 RED+GREEN extend `tests/Feature/AdminRouteScopingTest.php` — full-block hits every non-exempt route; login works. Las rutas se **enumeran en runtime** (`Route::getRoutes()` filtrando por middleware `license`), no se hardcodean: una lista fija se pudre en cuanto alguien agrega una ruta. Guardado con `assertNotEmpty` para que un set vacío falle en vez de pasar en verde. **Punto ciego conocido:** si UNA ruta pierde el middleware `license`, se cae del set en silencio (si lo pierden TODAS, el test falla).
- [x] 8.2 Update `AGENTE.MD`/README — `license.*` keys, dos ranuras de clave y por qué, `license:check` schedule, **revocación NO inmediata (offline-first, por diseño)**, activation flow, `scripts/license/make-dev-token.php`.
- [x] 8.3 Dev helper `scripts/license/make-dev-token.php` — local Ed25519 keypair + sample **JWS/EdDSA** signed token with `kid` header; private key never committed. (Mirrors the server's issuance so the client can be tested offline without the running server.)

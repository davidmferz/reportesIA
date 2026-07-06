# Tasks: Sistema de Licenciamiento

Strict TDD: GREEN follows RED.

## Batch 1 — Foundation

- [ ] 1.1 `config/license.php` (public_key, server_url, revalidation_hours=24, grace_days=14, client_id) + `.env.example` keys.
- [ ] 1.2 RED `tests/Unit/LicenseStateTest.php` — schema + `LicenseState::current()` + `payload` cast.
- [ ] 1.3 GREEN migration `create_license_states_table` (raw_token, payload json, valid_from, valid_until, max_users, last_check_at, last_check_result, last_seen_at, activated_at, activated_by FK) + `app/Models/LicenseState.php` (`LogsActivity`, `activityLogExclude=['raw_token']`, `current()`).
- [ ] 1.4 RED `tests/Unit/UserIsActiveTest.php` — default `is_active=true`; `scopeActive()` excludes inactive.
- [ ] 1.5 GREEN migration `add_is_active_to_users_table` + `User` fillable/cast/`scopeActive()` (mirror `ForbiddenWord`).

## Batch 2 — LicenseVerifier

- [ ] 2.1 RED `tests/Unit/LicenseVerifierTest.php::verify` — valid accepted; tampered/wrong-key/malformed rejected (`sodium_crypto_sign_keypair()` + `makeToken()` helper).
- [ ] 2.2 GREEN `app/Services/LicenseVerifier.php::verify()` — decode, `sodium_crypto_sign_verify_detached`, field validation, `LicenseInvalidException`.
- [ ] 2.3 RED extend — domain mismatch (`payload.domain` ≠ host) rejected.
- [ ] 2.4 GREEN domain-bind check in `verify()`.
- [ ] 2.5 RED `tests/Unit/LicenseStatusTest.php` — `resolveStatus()` covers Unlicensed/Valid/Grace/Blocked+rollback.
- [ ] 2.6 GREEN `app/Enums/LicenseStatus.php` + `LicenseVerifier::resolveStatus()`.
- [ ] 2.7 RED `tests/Feature/LicenseActivationTest.php` — valid token persists + audits; invalid rejects, no change.
- [ ] 2.8 GREEN `LicenseVerifier::activate(string $token, User $by)`.

## Batch 3 — Middleware + registration

- [ ] 3.1 RED `tests/Feature/LicenseEnforcementTest.php` — valid passes; grace banners; grace-exhausted redirects; `/up`+`routes/auth.php` exempt.
- [ ] 3.2 GREEN `app/Http/Middleware/EnsureLicenseIsValid.php` — `resolveStatus()` only, `View::share('licenseGraceUntil')`, redirect `license.activation.show`.
- [ ] 3.3 RED extend — grace→blocked audited once, not per-request.
- [ ] 3.4 GREEN transition-audit logic in middleware.
- [ ] 3.5 RED `tests/Feature/UserActiveEnforcementTest.php` — deactivated session evicted immediately; active unaffected.
- [ ] 3.6 GREEN `app/Http/Middleware/EnsureUserIsActive.php`.
- [ ] 3.7 GREEN register both aliases in `bootstrap/app.php` (mirror `admin`), append to dashboard/profile/admin groups.

## Batch 4 — license:check + scheduler + contract

- [ ] 4.1 RED `tests/Feature/LicenseCheckCommandTest.php` — `Http::fake()`: renewed replaces state; `revoked` invalidates+audits; unreachable state unchanged.
- [ ] 4.2 GREEN `app/Console/Commands/LicenseCheckCommand.php` (mirror `AuditTrainingExamplesCommand`) — `POST {server}/api/v1/licenses/{client_id}/validate`, `Http::timeout(10)->connectTimeout(5)->retry(1,500)`, re-verifies, bumps `last_seen_at`.
- [ ] 4.3 GREEN `->withSchedule(fn($s)=>$s->command('license:check')->daily())` in `bootstrap/app.php`.
- [ ] 4.4 RED `tests/Unit/LicenseRollbackTest.php` — `now() < last_seen_at` forces revalidation.
- [ ] 4.5 GREEN rollback detection in `resolveStatus()`.

## Batch 5 — Activation screen

- [ ] 5.1 RED `tests/Feature/LicenseControllerTest.php` — `show` (auth+admin, no `license` mw); `store` valid activates; invalid errors, no change.
- [ ] 5.2 GREEN `app/Http/Controllers/LicenseController.php` + `routes/web.php` (`license.activation.show/store`, auth+admin only).
- [ ] 5.3 GREEN view `resources/views/license/activation.blade.php` (layout `crm`).

## Batch 6 — UI banner

- [ ] 6.1 RED `tests/Feature/LicenseBannerTest.php` — renders when `licenseGraceUntil` shared, absent otherwise.
- [ ] 6.2 GREEN `resources/views/components/license-banner.blade.php`.
- [ ] 6.3 GREEN include `<x-license-banner />` in `layouts/app.blade.php` + `components/layouts/crm.blade.php`.

## Batch 7 — User capacity (Admin\UserController)

- [ ] 7.1 RED `tests/Feature/Admin/UserCapacityTest.php` — under cap ok; at cap rejects (create + reactivate).
- [ ] 7.2 GREEN `Admin\UserController::store()` rejects at `User::active()->count() >= LicenseState::current()?->max_users`.
- [ ] 7.3 GREEN `Admin\UserController::toggleActive()` applies same cap check; add route if missing.

## Batch 8 — Integration, docs, dev tooling

- [ ] 8.1 RED+GREEN extend `tests/Feature/AdminRouteScopingTest.php` — full-block hits every non-exempt route; login works.
- [ ] 8.2 Update `AGENTE.MD`/README — `license.*` keys, `license:check` schedule, activation flow.
- [ ] 8.3 Dev helper `scripts/license/make-dev-token.php` — local Ed25519 keypair + sample signed token; private key never committed.

# Archive Report: licenciamiento

**Date**: 2026-07-14
**Status**: ARCHIVED — verified and closed

---

## Final Status

The change is implemented, verified, and archived. Original verification
(`verify-report.md`) concluded **pass-with-warnings**; the two open items
tracked there have since been resolved:

- The "non-admin blocked user gets 403" gap was already confirmed **closed**
  in `verify-report.md` itself (`EnsureLicenseIsValid::block()` +
  `license.blocked.show` route, backed by `LicenseEnforcementTest` and
  `LicenseBlockedScreenTest`).
- The remaining spec-coverage gap for `user-capacity` ("No Hard-Delete for
  Capacity Management" — deactivating a user must not cascade-delete related
  `ai_generations`) was closed after the verify report was written, by adding
  `test_desactivar_usuario_no_borra_sus_ai_generations_historicas` to
  `tests/Feature/Admin/UserCapacityTest.php`. This test asserts that toggling
  `is_active` to `false` leaves the user's historical `AIGeneration` rows in
  place (`assertDatabaseHas`), proving the `onDelete('cascade')` FK path is
  never triggered by deactivation.

## Test Numbers (re-run at archive time)

```
php artisan test
Tests:    118 passed (308 assertions)
Duration: 2.12s
```

This is the full suite (up from 117 passed / 303 assertions at the time
`verify-report.md` was written) — the delta is exactly the one new
`UserCapacityTest` case plus its assertions. Command run directly via
`php artisan test` (equivalent to `composer test`; `composer` binary is not
on PATH in this environment). No build step exists for this Laravel/PHP
project and none was run, per project convention.

## Capabilities Promoted

Three capabilities moved from `openspec/changes/licenciamiento/specs/` to
`openspec/specs/` as **new (ADDED)** living specs — `openspec/specs/` was
empty prior to this archive, so no merge/diff logic was needed; the delta
specs already read as full specs (no `## ADDED/MODIFIED/REMOVED Requirements`
change-delta markers were present in the source files), so they were copied
verbatim:

| Capability | File | Requirements | Scenarios |
|---|---|---|---|
| License Validation | `openspec/specs/license-validation/spec.md` | 8 | 15 |
| License Enforcement | `openspec/specs/license-enforcement/spec.md` | 6 | 11 |
| User Capacity | `openspec/specs/user-capacity/spec.md` | 5 | 9 |

All requirements and scenarios from the change's delta specs were preserved
verbatim — nothing dropped or summarized.

## Known Non-Blocking Residuals

These were carried over from `verify-report.md` and remain open by design
(informational, not blocking):

1. **Scheduler registration verified structurally, not by firing.**
   `bootstrap/app.php` registers `$schedule->command('license:check')->daily()`
   (line ~21-26). This is confirmed by direct code inspection, but there is
   no test that exercises Laravel's scheduler actually *firing* the command
   — only the command's behavior once invoked is tested
   (`LicenseCheckCommandTest`). Consistent with typical Laravel testing
   practice (scheduler firing is framework-level, not app-level). A cheap
   follow-up would be a `php artisan schedule:list` / `app(Schedule::class)
   ->events()` assertion, but it is not required to close this change.

2. **Pint reports cosmetic style issues in 6 files.** Scoped to the 10
   files this change created/modified, `vendor/bin/pint --test` flags 6:
   `LicenseCheckCommand.php`, `LicenseInvalidException.php`,
   `Admin/UserController.php`, `LicenseController.php`,
   `EnsureLicenseIsValid.php`, `LicenseVerifier.php` — issues are purely
   stylistic (`concat_space`, `unary_operator_spaces`,
   `not_operator_with_successor_space`, `method_chaining_indentation`,
   `single_line_empty_body`), auto-fixable with plain `vendor/bin/pint`
   (no `--test`), and do not affect correctness or test results. Not run
   as part of the test suite or CI gate for this project.

## Archive Contents

`openspec/changes/archive/licenciamiento/`:
- `proposal.md`
- `exploration.md`
- `design.md`
- `specs/license-validation/spec.md`
- `specs/license-enforcement/spec.md`
- `specs/user-capacity/spec.md`
- `tasks.md` (43/43 complete)
- `reconciliation-jws.md` (records the mid-change pivot from a bespoke
  detached-Ed25519 token format to standard JWS/EdDSA, 2026-07-07)
- `verify-report.md` (pass-with-warnings, dated 2026-07-14)
- `archive-report.md` (this file)

## Source of Truth Updated

- `openspec/specs/license-validation/spec.md`
- `openspec/specs/license-enforcement/spec.md`
- `openspec/specs/user-capacity/spec.md`

### SDD Cycle Complete

The change has been fully planned, implemented, verified, and archived.
Ready for the next change.

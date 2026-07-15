# Proposal: Sistema de Licenciamiento (client-side)

## Intent

reportesIA se vende por instalación/cliente sin ningún mecanismo que valide o limite el uso. Se necesita un módulo cliente que verifique una licencia (fechas de validez + máximo de usuarios activos), avise durante una gracia y bloquee el sistema al vencer, sin acoplar la disponibilidad del cliente al uptime del license server.

## Scope

### In Scope
- Verificación de token de licencia firmado Ed25519 (offline) + revalidación periódica online.
- Persistencia del estado de licencia en tabla dedicada `license_states`.
- Middleware de enforcement: banner de warning en gracia → bloqueo total (solo pantalla de activación).
- Pantalla/flujo de activación de licencia (pegar/subir token) auditado vía ActivityLog.
- Campo `is_active` en `User` + enforcement de `max_users` en `Admin\UserController`.
- Comando `license:check` + `withSchedule()` para el phone-home diario.
- **Contrato** HTTP/token con el license server (formato + endpoint, versionado `v1`).

### Out of Scope
- Scaffolding del license server (proyecto separado del vendor): solo se define el contrato, no se implementa aquí — evita construir un backend que no vive en este repo.
- Feature flags / cuotas de consumo. Mecánica de despliegue del cliente. DNS/hosting hardening.

## Capabilities

### New Capabilities
- `license-validation`: verificación offline de firma Ed25519, revalidación periódica online, persistencia de estado, detección de retroceso de reloj, contrato con el license server.
- `license-enforcement`: middleware banner→bloqueo, rutas exentas, pantalla de activación.
- `user-capacity`: `is_active` en User + enforcement de `max_users` al crear/activar usuarios.

### Modified Capabilities
- None (no existen specs previos).

## Decisiones sobre preguntas abiertas

1. **Login exento del bloqueo, SÍ.** Se permite autenticarse; tras login todo redirige a la pantalla de licencia. Un admin DEBE poder entrar para ver el estado y re-activar; bloquear login dejaría al cliente sin forma de recuperarse. Exentas: `routes/auth.php`, rutas de activación, `/up`.
2. **Contrato híbrido.** Token = JSON `{client_id, domain, valid_from, valid_until, max_users, issued_at, token_version}` + firma detached Ed25519 (`sodium`) verificada contra public key en `config/license.php`. Revalidación: `POST {server}/api/v1/licenses/{client_id}/validate` → token renovado o `revoked`. Binding por dominio.
3. **Estado en tabla dedicada `license_states`** (modelo `LicenseState`, fila única), NO cache ni archivo: resiste `cache:clear` y audita consistente con el resto.
4. **Revalidación diaria** (`license.revalidation_hours=24`, configurable); **gracia 14 días** tras `valid_until` (`license.grace_days=14`, configurable). Ambos en `config/license.php`.
5. **Clock-tampering (v1, mínimo):** guardar `last_seen_at` (mayor validación exitosa); si `now() < last_seen_at`, forzar revalidación. Se documenta como limitación conocida (no criptográficamente robusto).
6. **Sesiones de usuarios desactivados por `max_users`:** el chequeo de `is_active` corre en cada request (middleware de sesión); un usuario desactivado es expulsado en su siguiente request, no espera a que expire la sesión.

## Approach

Middleware barato (B.2): lee `license_states` + compara fechas/gracia localmente (sin red ni crypto por request). Un job periódico (A.3) hace el phone-home real, verifica firma y refresca token/estado. `is_active` replica el patrón de `ForbiddenWord` (evita hard-delete que CASCADEa `ai_generations`).

## Affected Areas

| Area | Impact | Description |
|------|--------|-------------|
| `bootstrap/app.php` | Modified | alias middleware `license`; `withSchedule()` |
| `app/Http/Middleware/CheckLicense.php` | New | enforcement banner→bloqueo |
| `app/Models/LicenseState.php` + migración | New | estado de licencia |
| `app/Services/LicenseVerifier.php` | New | verificación firma + revalidación |
| `app/Console/Commands/LicenseCheckCommand.php` | New | phone-home diario |
| `app/Http/Controllers/LicenseController.php` + vistas | New | activación + banner |
| `app/Models/User.php` + migración | Modified | `is_active` + `scopeActive` |
| `app/Http/Controllers/Admin/UserController.php` | Modified | enforce `max_users` |
| `config/license.php`, `.env.example` | New | public key, server URL, gracia |
| `resources/views/.../app.blade.php`, `crm.blade.php` | Modified | `<x-license-banner />` |
| `tests/Feature/LicenseEnforcementTest.php` | New | tests de enforcement |

## Risks

| Risk | Likelihood | Mitigation |
|------|------------|------------|
| Contrato con server inexistente | High | versionar token/endpoint (`token_version`, `/v1/`) |
| Clock-tampering | Med | `last_seen_at`; documentar límite v1 |
| Sesión de usuario desactivado sigue activa | Med | chequeo `is_active` por request |
| Bloqueo global rompe rutas legítimas | Med | lista blanca explícita + test de rutas exentas |

## Rollback Plan

Remover alias `license` y `withSchedule()` de `bootstrap/app.php` (desactiva enforcement sin tocar datos). Migraciones `license_states` e `is_active` son `down()`-ables; `is_active` default `true` no altera comportamiento previo. Config/vistas nuevas son inertes sin el middleware registrado.

## Dependencies

- Ext PHP `sodium` (ya cargada). License server del vendor (externo) para revalidación real; el cliente opera offline contra el último token válido si no responde.

## Success Criteria

- [ ] Licencia vencida + gracia excedida → todo redirige a pantalla de activación (test).
- [ ] Durante gracia se muestra banner y el sistema sigue operativo (test).
- [ ] Crear usuario sobre `max_users` es rechazado (test).
- [ ] Token con firma inválida o dominio distinto es rechazado (test).
- [ ] Login funciona bajo bloqueo; resto queda restringido (test).
- [ ] Activación/renovación/entrada-a-bloqueo quedan auditadas en ActivityLog.

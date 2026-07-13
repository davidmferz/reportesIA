# Reconciliation: cliente reportesIA ↔ licensing server (JWS)

> 2026-07-07. Este change (`licenciamiento`) es el **Change B (cliente)** del plan
> de licenciamiento centralizado. El **Change A (server)** es
> `~/Documents/licensing-server/openspec/changes/bootstrap-licensing-server/`.

## Qué cambió y por qué

Se construye un **servidor de licencias independiente** (Java 21 / WildFly 36 /
PrimeFaces 15 / Postgres 17 / Docker) que administra las licencias de toda la
cartera de la consultora por API. Se decidió que el token de licencia sea
**JWS con alg EdDSA (Ed25519)** en vez del formato original de este change
(Ed25519 **detached** + JSON propio).

**Motivo:** el firmante ahora es Java y los clientes son heterogéneos. Un JWS
estándar elimina el riesgo de canonicalización cross-language (que Java y PHP
serialicen el JSON byte-a-byte distinto) y trae `kid`/rotación y librerías
maduras en todo stack.

## Qué NO cambia

La **arquitectura del cliente se mantiene**: verificación **offline** de firma
por request (barata), **phone-home** diario (`license:check`), **gracia** de 14
días, estado en `license_states`, middleware de enforcement, `max_users`,
detección de rollback de reloj. El threat model v1 sigue igual (enforcement
disuasivo/contractual; la firma es la barrera criptográfica).

## Deltas concretos en este change

| Área | Antes | Ahora |
|---|---|---|
| `license-validation` → *Token Signature Verification* | Ed25519 detached `sodium_crypto_sign_verify_detached` | **JWS/EdDSA**, `alg=EdDSA` forzado (rechaza `alg:none`/confusión), selección de pública por `kid` |
| `license-validation` → *Public Key Resolution by kid* (NUEVO) | pública única en `config/license.php` | pública por `kid` desde config y/o **JWKS** cacheado (`GET {server}/api/v1/products/{product_id}/jwks`) |
| `license-enforcement` → *Non-Admin Blocked Screen* (NUEVO) | (gap: no-admin bloqueado → 403 en pantalla admin) | no-admin bloqueado → pantalla read-only "contactá a tu administrador" (200); admin → activación |
| Claims del token | `token_version` | `schema_version` + `kid`, `iss`, `exp` |
| Tasks | Batch 2 detached; helper dev detached | Batch 2 JWS + resolución por `kid` (2.9/2.10); 3.8/3.9 no-admin; 5.4/5.5 pantalla bloqueado; 8.3 helper JWS |

## Contrato con el server (sin cambios de forma)

`POST {server}/api/v1/licenses/{client_id}/validate`
req `{domain, schema_version, current_token}` →
res `{status: "active"|"revoked", token: "<nuevo JWS|null>", reason}`.
Server caído/5xx ⇒ el cliente sigue con su token local hasta `valid_until` (+gracia).

## Blindaje antes de implementar

Existe (Change A, Fase 8) un **test cross-check** que firma un JWS en el server y
lo verifica como lo haría el cliente. Correrlo/replicarlo del lado cliente
(helper `make-dev-token.php`, task 8.3) permite testear el verifier del cliente
**sin** el server corriendo y garantiza que ambos extremos hablan el mismo JWS.

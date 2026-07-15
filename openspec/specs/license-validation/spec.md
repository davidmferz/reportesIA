# License Validation Specification

## Purpose

Offline JWS/EdDSA signature verification, persisted license state, and daily online revalidation, so the client operates independent of license-server uptime. (Reconciled 2026-07-07 with the centralized licensing server — see `reconciliation-jws.md`.)

## Requirements

### Requirement: Token Signature Verification (JWS/EdDSA)

The system MUST verify a license token as a **JWS with algorithm `EdDSA` (Ed25519)**, selecting the verification public key by the token header's `kid`, and MUST reject tokens whose signature fails, whose header `alg` is not `EdDSA` (in particular MUST reject `alg: none` and any algorithm-confusion attempt), that are malformed, or that are missing required claims (`client_id`, `domain`, `valid_from`, `valid_until`, `max_users`, `issued_at`, `schema_version`, `kid`). Verification MUST be performed with a JWS-capable library (e.g. `web-token/jwt-*` or an EdDSA-capable JWT lib backed by `sodium`), not by ad-hoc detached-signature handling.

#### Scenario: Valid JWS token accepted
- GIVEN a JWS EdDSA token issued and signed by the licensing server's private key for this product
- WHEN the token is verified with the public key matching its `kid`
- THEN verification succeeds and claims are extracted

#### Scenario: Tampered or garbage token rejected
- GIVEN a JWS with a modified payload or signature, or an arbitrary non-JWS string
- WHEN the token is verified
- THEN verification MUST fail and no license state is updated

#### Scenario: Algorithm confusion rejected
- GIVEN a token whose header declares `alg: none` or a non-EdDSA algorithm
- WHEN the token is verified
- THEN verification MUST fail regardless of any signature present

#### Scenario: Token signed with wrong/unknown key rejected
- GIVEN a JWS whose `kid` matches no configured public key, or that was signed with a private key not matching the configured public key for its `kid`
- WHEN the token is verified
- THEN verification MUST fail

### Requirement: Public Key Resolution by `kid`

The system MUST resolve the verification public key by the token's `kid`, from either a locally configured key set (`config/license.php`) or a cached JWKS obtained from the server's `GET /api/v1/products/{productId}/jwks`, and MUST NOT trust a network JWKS response to override a locally pinned key without the operator opting in. This enables server-side key rotation without a client redeploy.

#### Scenario: Rotated key verified via JWKS
- GIVEN the server rotated to a new `kid` and the client has the new public key in its configured/cached JWKS
- WHEN a token signed with the new `kid` is verified
- THEN verification succeeds using the public key selected by `kid`

#### Scenario: Old kid still verifies during overlap
- GIVEN a token previously issued under an older `kid` whose public key is still configured
- WHEN it is verified before its `valid_until`
- THEN verification succeeds

### Requirement: Domain Binding

The system MUST reject a token whose `domain` field does not match the application's host.

#### Scenario: Domain mismatch rejected
- GIVEN a validly signed token issued for `otro-cliente.com`
- WHEN verified on an instance running at `cliente.com`
- THEN verification MUST fail with a domain-mismatch reason

### Requirement: License State Persistence

The system MUST persist license state (token fields, verification timestamp, `last_seen_at`) in a dedicated `license_states` table via a single-row `LicenseState` model, not cache or file storage, and MUST audit changes via `LogsActivity`.

#### Scenario: Successful verification persists state
- GIVEN a token passes signature and domain verification
- WHEN verification completes
- THEN `LicenseState` is created/updated and an ActivityLog entry is recorded

### Requirement: Daily Online Revalidation

The system MUST provide a `license:check` command, scheduled via `withSchedule()` every `license.revalidation_hours` (default 24), that POSTs to `{server}/api/v1/licenses/{client_id}/validate` (v1 contract) for a renewed token or revocation.

#### Scenario: Server confirms renewal
- GIVEN `license:check` runs and the server returns a renewed, validly signed token
- WHEN the response is processed
- THEN the new token replaces the persisted state

#### Scenario: Server unreachable — offline tolerance
- GIVEN `license:check` runs and the server times out or is unreachable
- WHEN the command finishes
- THEN the persisted `LicenseState` is left unchanged and the system keeps operating under the last valid token until its `valid_until`

#### Scenario: Server signals revocation
- GIVEN `license:check` runs and the server responds `revoked`
- WHEN the response is processed
- THEN the persisted state is marked invalid and audited

### Requirement: Activation Flow

The system MUST provide an activation screen where a pasted/uploaded token is verified (signature + domain) before persisting, with the outcome audited.

#### Scenario: Successful activation
- GIVEN an admin submits a validly signed token for this domain
- WHEN activation is processed
- THEN the state is persisted and an ActivityLog "license activated" entry is created

#### Scenario: Failed activation shows error
- GIVEN an admin submits an invalid or tampered token
- WHEN activation is processed
- THEN the token is rejected, no state changes, and an error is shown

### Requirement: Clock-Rollback Detection

The system MUST store `last_seen_at` as the timestamp of the latest successful validation and, if `now() < last_seen_at`, MUST distrust local time and force an online `license:check` revalidation instead of relying on local date comparisons.

#### Scenario: Clock rolled back triggers forced revalidation
- GIVEN `last_seen_at` was recorded at T and the system clock is set to before T
- WHEN a license check runs
- THEN the system detects `now() < last_seen_at` and forces `license:check` instead of trusting local expiry math

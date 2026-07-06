# License Validation Specification

## Purpose

Offline Ed25519 signature verification, persisted license state, and daily online revalidation, so the client operates independent of license-server uptime.

## Requirements

### Requirement: Token Signature Verification

The system MUST verify a license token's detached Ed25519 signature (`sodium_crypto_sign_verify_detached`) against the public key in `config/license.php`, and MUST reject tokens that fail verification, are malformed, or are missing required fields (`client_id`, `domain`, `valid_from`, `valid_until`, `max_users`, `issued_at`, `token_version`).

#### Scenario: Valid signed token accepted
- GIVEN a token payload and signature produced by the license server's private key
- WHEN the token is verified
- THEN verification succeeds and payload fields are extracted

#### Scenario: Tampered or garbage token rejected
- GIVEN a token payload modified after signing, or an arbitrary non-JSON string
- WHEN the token is verified
- THEN verification MUST fail and no license state is updated

#### Scenario: Token signed with wrong key rejected
- GIVEN a token signed with a private key not matching the configured public key
- WHEN the token is verified
- THEN verification MUST fail

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

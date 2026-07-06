# License Enforcement Specification

## Purpose

Middleware that reads persisted license state and enforces a warning banner during grace or a full block when expired/invalid, with an explicit route allowlist and audit trail.

## Requirements

### Requirement: Cheap Per-Request State Read

The `CheckLicense` middleware MUST NOT perform network calls or cryptographic verification per request; it MUST only read the persisted `LicenseState` and compare `valid_until`/grace/status locally.

#### Scenario: Valid license passes through
- GIVEN persisted state has `valid_until` in the future and status `valid`
- WHEN any non-exempt route is requested
- THEN the request proceeds normally with no banner

### Requirement: Grace Period Warning Banner

When `now()` falls between `valid_until` and `valid_until + license.grace_days` (default 14, configurable), the system MUST allow full access but MUST render a warning banner on every page.

#### Scenario: Expired but within grace shows banner
- GIVEN `valid_until` was 5 days ago and `grace_days` is 14
- WHEN any page is requested
- THEN the page renders normally with the license warning banner visible

#### Scenario: Grace exhausted triggers block
- GIVEN `valid_until` was 20 days ago and `grace_days` is 14
- WHEN a non-exempt route is requested
- THEN the middleware redirects to the activation screen instead of the requested page

### Requirement: Full Block on Expired or Invalid License

Outside grace, or when license status is `invalid`/`revoked`, the system MUST block all non-exempt routes by redirecting to the activation screen.

#### Scenario: Blocked user redirected from dashboard
- GIVEN license status is `expired` and grace is exhausted
- WHEN a user requests `/dashboard`
- THEN they are redirected to the activation screen

### Requirement: Route Allowlist

The middleware MUST exempt `routes/auth.php` routes (login, password reset), activation routes, and `/up` from blocking, regardless of license state.

#### Scenario: Admin can log in while blocked
- GIVEN the license is expired and grace is exhausted
- WHEN a user submits valid credentials to the login route
- THEN authentication succeeds and the user is redirected to the activation screen, not blocked at login

#### Scenario: Health check remains reachable while blocked
- GIVEN the license is expired and grace is exhausted
- WHEN `/up` is requested
- THEN it responds normally, unaffected by the license middleware

### Requirement: License Event Auditing

Transitions into grace, into block, and any middleware-triggered redirect-to-activation MUST be recorded via ActivityLog on state transitions (not per request).

#### Scenario: Transition into block is audited
- GIVEN the license transitions from grace to blocked because `grace_days` is exhausted
- WHEN this is detected
- THEN an ActivityLog entry describing the transition is created

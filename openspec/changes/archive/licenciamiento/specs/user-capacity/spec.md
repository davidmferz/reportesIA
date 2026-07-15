# User Capacity Specification

## Purpose

Enforces the license's `max_users` limit via a non-destructive `is_active` flag on `User`, mirroring `ForbiddenWord::active` + `scopeActive()`, with per-request eviction of deactivated users.

## Requirements

### Requirement: is_active Flag on User

The `users` table MUST have an `is_active` boolean column (default `true`), and `User` MUST expose it as fillable + boolean cast plus a `scopeActive()` query scope, following the `ForbiddenWord` pattern.

#### Scenario: Default new user is active
- GIVEN a new user is created without specifying `is_active`
- WHEN the record is persisted
- THEN `is_active` defaults to `true`

### Requirement: Capacity Enforcement on User Creation

`Admin\UserController::store()` MUST reject creating a new user when `User::active()->count()` already equals or exceeds the license's `max_users`, returning a validation error without persisting the user.

#### Scenario: Creation allowed under cap
- GIVEN `max_users` is 10 and 9 users are currently active
- WHEN an admin creates a new user
- THEN the user is created successfully and is active

#### Scenario: Creation rejected at cap
- GIVEN `max_users` is 10 and 10 users are currently active
- WHEN an admin attempts to create a new user
- THEN the request is rejected with a capacity error and no user record is created

### Requirement: Capacity Enforcement on Reactivation

Reactivating a previously deactivated user (`is_active` false to true) consumes a capacity slot and MUST be rejected under the same at-cap condition as creation.

#### Scenario: Reactivation rejected at cap
- GIVEN `max_users` is 10, 10 users are active, and one additional user is currently inactive
- WHEN an admin attempts to reactivate the inactive user
- THEN the request is rejected with a capacity error and the user remains inactive

### Requirement: Per-Request Session Eviction

A session-level check MUST verify `is_active` on every authenticated request; when the authenticated user's `is_active` is `false`, the system MUST log them out and redirect to login on that same request, without waiting for session expiry.

#### Scenario: Deactivated user with live session is ejected
- GIVEN a user is logged in with an active session and an admin subsequently sets `is_active = false`
- WHEN the deactivated user's browser makes its next request
- THEN the middleware logs the session out and redirects to login

#### Scenario: Active user's session is unaffected
- GIVEN a user is logged in and remains `is_active = true`
- WHEN they make a request
- THEN the request proceeds normally with no forced logout

### Requirement: No Hard-Delete for Capacity Management

The system MUST NOT require deleting `User` records to free capacity; deactivation (`is_active = false`) is the sole mechanism, preserving user history and related `ai_generations` records.

#### Scenario: Deactivating a user preserves related data
- GIVEN a user has existing `ai_generations` records
- WHEN an admin sets the user to `is_active = false`
- THEN the user record and all related `ai_generations` remain intact and only `is_active` changes

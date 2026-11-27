# Phase 04: Admin dashboard

## Goal

Provide a small authenticated interface for a fictional spa administrator to review and manage appointments.

## Tasks

- [x] Seed one fictional admin with a securely generated password hash.
- [x] Implement sign-in, sign-out, secure session settings, timeout, and session regeneration.
- [x] Add session-scoped login throttling/backoff appropriate to the demo deployment.
- [x] Protect every admin route server-side.
- [x] Build an upcoming appointment dashboard showing the assigned therapist, with status filtering. Add date filtering/pagination if the demo data grows.
- [x] Build an appointment detail view with minimally necessary customer information.
- [x] Enforce allowed status transitions in a service.
- [x] Protect all implemented admin mutations with CSRF tokens, including status changes.
- [ ] Add broader authentication, authorization, and filtering integration tests.
- [x] Add focused unit coverage for allowed, terminal, and unknown appointment status transitions.
- [x] Replace the README demo credential placeholder with fictional demo credentials. Do not reuse these credentials in a deployed environment.

## Acceptance criteria

- Anonymous requests cannot view or mutate admin data.
- Login failures do not reveal account existence; successful login rotates the session ID.
- An administrator can filter, inspect, and validly transition appointments.
- Terminal or stale status changes are rejected without corrupting state.

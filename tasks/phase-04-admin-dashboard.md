# Phase 04: Admin dashboard

## Goal

Provide a small authenticated interface for a fictional spa administrator to review and manage appointments.

## Tasks

- [ ] Seed one fictional admin with a securely generated password hash.
- [ ] Implement sign-in, sign-out, secure session settings, timeout, and session regeneration.
- [ ] Add login throttling/backoff appropriate to the demo deployment.
- [ ] Protect every admin route server-side.
- [ ] Build an upcoming appointment dashboard showing the assigned therapist, with date/status filters and pagination if needed.
- [ ] Build an appointment detail view with minimally necessary customer information.
- [ ] Enforce allowed status transitions in a service.
- [ ] Protect all mutations and sign-out with CSRF tokens.
- [ ] Add authentication, authorization, filtering, and transition tests.
- [ ] Replace the README demo credential placeholder with fictional credentials and handling notes.

## Acceptance criteria

- Anonymous requests cannot view or mutate admin data.
- Login failures do not reveal account existence; successful login rotates the session ID.
- An administrator can filter, inspect, and validly transition appointments.
- Terminal or stale status changes are rejected without corrupting state.

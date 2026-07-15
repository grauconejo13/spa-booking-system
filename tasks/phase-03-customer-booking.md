# Phase 03: Customer booking

## Goal

Allow a visitor to browse services and create one valid, conflict-free appointment through an accessible server-rendered flow.

## Tasks

- [ ] Build the active service catalogue and service detail views.
- [ ] Implement business-hours and time-slot calculation in a testable service.
- [ ] Build booking details, review, submit, and confirmation screens.
- [ ] Validate service, date, time, name, email, phone, and note on the server.
- [ ] Add session-backed CSRF protection and safe form repopulation.
- [ ] Generate unpredictable public appointment references.
- [ ] Snapshot service name, duration, and price at booking time.
- [ ] Prevent overlapping pending/confirmed appointments transactionally.
- [ ] Add optional vanilla JavaScript enhancement without making it required.
- [ ] Test valid bookings, boundaries, validation failures, stale slots, and concurrent collision attempts.
- [ ] Update user-flow and security documentation with implementation details.

## Acceptance criteria

- A visitor can complete the documented booking flow without JavaScript.
- Invalid, past, inactive, and conflicting requests are rejected clearly.
- Refreshing or double-submitting does not silently create duplicate/conflicting appointments.
- Confirmation output does not expose sensitive customer details in the URL.

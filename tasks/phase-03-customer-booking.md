# Phase 03: Customer booking

## Goal

Allow a visitor to browse services and create one valid, therapist-assigned, conflict-free appointment through an accessible server-rendered flow.

## Tasks

- [x] Load and render the active service catalogue from MySQL with empty and failure states.
- [x] Build the service detail view with active qualified therapists.
- [x] Add a read-only booking entry page with qualified therapist and "any available therapist" choices.
- [x] Implement exception-aware per-therapist availability and 30-minute time-slot previews.
- [x] Add query-backed time selection.
- [x] Add progressive booking steps with preserved `#booking-flow` position.
- [x] Show accessible per-therapist availability states without hiding unavailable therapists.
- [x] Build the customer-details form and non-persisting booking review state.
- [x] Validate and safely repopulate customer name, email, phone, and notes on the server.
- [x] Add session-backed CSRF protection to customer-details review submission.
- [x] Present booking preparation as a four-step horizontal wizard with validated transitions.
- [ ] Build booking submission and confirmation screens.
- [ ] Revalidate all booking inputs at appointment submission.
- [ ] Generate unpredictable public appointment references.
- [ ] Snapshot service name, duration, and price at booking time.
- [ ] Validate that a selected therapist is active, qualified, available, and free for the full interval.
- [ ] Deterministically assign and persist a qualified therapist for "any available therapist" bookings.
- [ ] Prevent overlapping pending/confirmed appointments for the assigned therapist transactionally.
- [ ] Add optional vanilla JavaScript enhancement without making it required.
- [ ] Test valid bookings, boundaries, validation failures, stale slots, and concurrent collision attempts.
- [ ] Update user-flow and security documentation with implementation details.

## Acceptance criteria

- A visitor can complete the documented booking flow without JavaScript.
- Invalid, past, inactive, unqualified, unavailable, and conflicting requests are rejected clearly.
- Refreshing or double-submitting does not silently create duplicate/conflicting appointments.
- Confirmation output does not expose sensitive customer details in the URL.

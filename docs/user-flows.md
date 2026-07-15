# User flows

## Customer books an appointment

1. Visitor opens the service catalogue.
2. Visitor selects an active service and views its duration and price.
3. Visitor chooses a future date.
4. Server calculates valid times from business hours, service duration, interval, and blocking appointments.
5. Visitor selects a time and enters name, email, optional phone, and optional note.
6. Visitor reviews the request and submits a CSRF-protected form.
7. Server normalizes and validates all fields, reloads the service, and rechecks availability.
8. Within a transaction, the server prevents a collision and creates a `pending` appointment with snapshot values and a random reference.
9. Visitor sees a confirmation page with the appointment summary and reference, but no sensitive data in the URL.

Failure paths:

- Invalid input returns the form with safe values and clear field errors.
- A time taken between selection and submission returns the visitor to time selection with an explanation.
- An inactive/missing service or past date cannot be booked.
- An unexpected persistence error shows a generic retry message and is safely logged.

## Administrator signs in

1. Administrator opens the sign-in page.
2. Administrator submits email and password with a CSRF token.
3. Server applies validation and appropriate login throttling, then verifies the stored password hash.
4. On success, the server regenerates the session ID and redirects to the dashboard.
5. On failure, the page shows one generic credential error without revealing whether the email exists.

## Administrator manages appointments

1. Authenticated administrator opens the dashboard.
2. Dashboard defaults to upcoming appointments and offers date/status filters.
3. Administrator selects an appointment to view its details.
4. Administrator submits an allowed status transition through a CSRF-protected form.
5. Server rechecks authentication, authorization, current state, and target state.
6. Server updates the appointment and redirects with a success message.

Conflicting or stale changes return a clear non-destructive error. Admin pages never rely on client-side hiding to enforce permissions.

## Administrator signs out

1. Administrator submits the sign-out action.
2. Server validates CSRF, clears authentication state, invalidates/regenerates the session, and expires the cookie where possible.
3. Server redirects to the sign-in page.

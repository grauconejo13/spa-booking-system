# User flows

## Customer books an appointment

1. Visitor opens the service catalogue.
2. Visitor follows `/services/{id}` to view the active service's description, duration, price, and
   currently active qualified therapists.
3. Visitor follows `/book/{serviceId}`, selects a qualified therapist preference or chooses
   "any available therapist," and chooses a non-past date.
4. Server previews 30-minute-interval start times using recurring hours, date exceptions, service
   duration, and pending or confirmed appointments. "Any" results merge identical starts without
   assigning a therapist.
5. Visitor selects a freshly calculated time. The server rejects malformed or unavailable time values
   and retains every candidate therapist for an "any" selection without assigning one.
6. Visitor enters a required name, valid email, required phone number, and optional note. The current
   customer-details screen is non-submitting and clearly states that no appointment is reserved.
7. Visitor reviews the request and submits a CSRF-protected form.
8. Server normalizes and validates all fields, reloads the service and therapist choice, and rechecks availability.
9. Within a transaction, the server locks/rechecks a qualified therapist, assigns that therapist (deterministically when "any" was chosen), prevents a per-therapist collision, and creates a `pending` appointment with snapshot values and a random reference.
10. Visitor sees a confirmation page with the appointment summary, assigned therapist, and reference, but no sensitive data in the URL.

Failure paths:

- Invalid input returns the form with safe values and clear field errors.
- A time taken between selection and submission returns the visitor to time selection with an explanation.
- A selected therapist who is no longer qualified, active, or available cannot be booked; an "any" request fails clearly if no candidate remains.
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

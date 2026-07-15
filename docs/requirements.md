# Requirements

## Product goal

Demonstrate end-to-end PHP 8.3 and MySQL 8 development through a credible but intentionally small fictional spa appointment application.

## Actors

- **Visitor/customer:** browses services and submits an appointment request.
- **Administrator:** signs in, reviews appointments, and changes appointment status.

## Functional requirements

### Public experience

- Display active spa services with a name, description, duration, and price.
- Allow a customer to select one service, a future date, and an available start time.
- Collect a customer name, email address, and optional phone number and note.
- Revalidate all submitted values and availability on the server.
- Create a pending appointment and show a non-sensitive confirmation reference.
- Prevent overlapping active appointments for the single fictional treatment room/resource.

### Administration

- Authenticate one or more seeded fictional admin users.
- List appointments with date, service, customer, and status.
- Filter appointments by date and status.
- View appointment details.
- Change an appointment between `pending`, `confirmed`, `completed`, and `cancelled`, subject to documented transition rules.
- Sign out and invalidate the authenticated session.

## Non-functional requirements

- Support PHP 8.3 and MySQL 8.
- Use PDO prepared statements and `utf8mb4` throughout.
- Render useful pages without JavaScript; JavaScript may enhance interaction.
- Follow WCAG-minded basics: semantic headings, labels, focus visibility, keyboard use, understandable errors, and sufficient contrast.
- Use responsive layouts for current mobile and desktop browsers.
- Keep the architecture understandable without a PHP framework or a large dependency graph.
- Provide deterministic fictional seed data and automated tests for core rules.
- Avoid collecting or presenting real personal, medical, or payment information.

## Out of scope

- Real payments, gift cards, refunds, taxes, or invoicing
- Email/SMS delivery and third-party calendar integrations
- Multiple locations, rooms, practitioners, resources, or time zones
- Customer accounts, loyalty programs, reviews, or waitlists
- Recurring appointments and group bookings
- Medical histories, treatment notes, or regulated health data
- Production-grade capacity planning, high availability, or audit compliance

## Initial business rules

- The demo represents one spa location and one bookable treatment resource.
- Services are scheduled in fixed durations; appointment end time is derived from start time plus service duration at creation.
- Appointments must fall within configured business hours and start on a configured interval.
- `cancelled` appointments do not block availability; pending and confirmed appointments do.
- Historical appointment values should remain meaningful if a service later changes, so appointments snapshot service name, duration, and price.
- Store appointment timestamps in UTC and convert at the presentation boundary using the configured spa time zone.

## Acceptance boundary for the portfolio release

The release is complete when a reviewer can set up the database, load fictional data, complete one booking, observe collision prevention, sign in as the demo admin, manage that booking, and run the documented quality suite successfully.

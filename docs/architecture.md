# Architecture

## Approach

The application uses a small MVC-inspired, server-rendered architecture. It borrows separation of concerns from MVC without introducing a framework, ORM, event bus, or generic abstraction layer.

```text
Browser
  -> public/index.php (front controller)
  -> route match in routes/web.php
  -> controller
  -> service (business/use-case rules)
  -> repository (PDO prepared statements)
  -> MySQL

Controller -> PHP view -> HTML response
```

## Request lifecycle

1. The web server sends all application requests to `public/index.php`.
2. The front controller loads Composer, environment/configuration, shared dependencies, central error handling, and route declarations. Session/security middleware is added only when later phases require it.
3. A minimal router matches HTTP method and path to a controller action.
4. The controller reads request data, delegates validation/use cases, and selects a response or view.
5. Services enforce business rules and coordinate repository calls and transactions.
6. Repositories execute PDO prepared statements and map rows to domain models or purpose-built result objects.
7. Views receive explicit data and render escaped HTML.

Phase 1 provides the router, response object, base controller, view renderer, environment loader, PDO connection factory, explicit dependency wiring, shared layout, and central error handling. Phase 2 adds domain models, focused PDO repositories, a migration ledger/runner, and an idempotent fictional seeder. Phase 3 begins by wiring the public services route to the PDO-backed service repository while the home page retains its temporary featured-service content.

## Layer boundaries

- **Controllers** know HTTP concepts but not SQL.
- **Services** know use cases and business rules but not HTML.
- **Repositories** know persistence and SQL but not request/session globals.
- **Models** hold domain state and simple invariant behavior; they do not save themselves.
- **Validation** turns untrusted scalar input into validated data and structured errors.
- **Views** render provided data and may use small, escaped presentation helpers.

Dependencies point inward from HTTP and persistence concerns toward application/domain rules. For this project, plain constructor wiring in a bootstrap file is preferred to a dependency-injection container.

## Configuration

`.env` holds local values and is excluded from Git. `.env.example` documents required keys. Configuration files return typed or well-documented arrays built from environment values; application code receives configuration rather than reading environment variables throughout the codebase.

`config/database.php` provides a side-effect-free configuration array. The PDO factory creates connections on demand with exceptions enabled, native prepares, and associative fetch mode. It converts driver connection failures to a credential-free application exception. Phase 2 repositories receive the resulting PDO connection through their constructors.

## Routing and rendering

Routes are declared explicitly in `routes/web.php`, grouped conceptually into public and future admin routes. The router supports named single-segment parameters such as `/services/{id}` and passes matched values to controller callables. Route declarations remain readable and contain no business logic. PHP templates are chosen over a template engine to keep dependencies and learning surface small.

## Error handling and logging

- Development may show detailed exceptions; production-facing mode returns generic error pages.
- Exceptions are logged to `storage/logs` without secrets or unnecessary personal data.
- Expected validation failures return field-level feedback and preserve safe input.
- Unexpected failures are handled centrally by the front controller.

The booking route supports GET previews and POST-only customer-details and final-confirmation boundaries. The POST
uses a session-backed CSRF token, recalculates the selected time from repository schedule data, and
renders a non-persisting review state. Customer contact values remain in the request body and are never
placed in redirect or query parameters. An explicit step value requests wizard navigation, while the
controller remains authoritative: each transition is reduced to the latest step whose prerequisites are valid.
Final creation begins a transaction, locks active qualified therapist rows in stable ID order with
`SELECT ... FOR UPDATE`, reloads availability and blocking appointments, assigns a therapist, and inserts
the pending appointment before committing. These parent-row locks serialize competing bookings even when
no overlapping appointment row exists yet, avoiding the phantom gap left by an overlap query alone.

## Architectural decisions

### ADR-001: Framework-free, MVC-inspired structure

**Decision:** Use a front controller with explicit routes, controllers, services, repositories, models, validation, and PHP views.

**Rationale:** It visibly demonstrates core PHP, HTTP, and SQL knowledge while keeping the code navigable. It avoids rebuilding a full framework: helpers are added only when needed.

### ADR-002: PDO repositories instead of an ORM

**Decision:** Keep SQL in repositories and use PDO prepared statements.

**Rationale:** The project should demonstrate SQL/schema skill and safe database access. The small domain does not justify ORM complexity.

### ADR-003: Server-rendered pages with progressive enhancement

**Decision:** HTML responses are complete without JavaScript; vanilla JavaScript improves availability selection and confirmation UX.

**Rationale:** This is accessible, resilient, and appropriate for a PHP portfolio application.

### ADR-004: One location with therapist-based capacity

**Decision:** Model one fictional spa location with multiple therapists. Services and therapists have a many-to-many qualification relationship, therapists own their availability, and every appointment is assigned to one therapist.

**Rationale:** Therapist choice is credible for a spa portfolio while keeping collision rules understandable. Rooms, equipment, multiple locations, payroll, and other operational resource planning remain explicitly excluded.

### ADR-005: Service snapshots on appointments

**Decision:** Store the booked service name, duration, and price on each appointment in addition to `service_id`.

**Rationale:** Historical bookings remain accurate when catalogue details change. The small, intentional duplication is preferable to misleading history.

### ADR-006: UTC persistence and configured display zone

**Decision:** Persist appointment instants in UTC and convert at boundaries using `APP_TIMEZONE`.

**Rationale:** It makes time handling explicit and avoids dependence on server/database session time zones.

### ADR-007: Deterministic assignment for "any therapist"

**Decision:** Availability queries may show a time when at least one qualified therapist can perform the service. At booking time, the service rechecks candidates in a transaction and assigns the available therapist with the fewest blocking appointments that day, then the lowest therapist ID.

**Rationale:** A concrete therapist is required for collision protection. Deterministic selection is testable and avoids implying workload optimization, payroll, or preference logic that is outside scope.

### ADR-008: PHP-native, ledger-backed migrations

**Decision:** Use ordered PHP migration files implementing explicit `up` and `down` methods, tracked in a small `migrations` table. Use a purpose-built transactional seeder for committed demo records.

**Rationale:** The schema is small and does not justify a framework or migration package. PHP migrations keep DDL visible, share the existing PDO configuration, support a clearly labeled latest-batch rollback, and avoid adding a production dependency. MySQL DDL implicitly commits, so a migration is recorded only after its `up` method completes; a partially failed DDL migration must be inspected before retrying.

### ADR-009: Focused concrete repositories

**Decision:** Provide separate service, therapist, appointment, and administrator repositories without a generic base repository or interfaces.

**Rationale:** Each query has domain-specific selection and mapping behavior. There is no current alternate implementation that would justify interfaces; tests exercise the concrete persistence boundary against an explicitly configured disposable MySQL database.

### ADR-010: Deterministic availability previews

**Decision:** Generate candidate starts at 30-minute intervals in the configured spa timezone. A
date exception replaces recurring windows for that therapist and date. Compare slots with appointment
intervals in UTC, and merge identical "any therapist" starts while retaining all candidate therapist IDs.

**Rationale:** Pure calculation remains deterministic and unit-testable, while repositories stay focused
on loading schedule inputs. Previewing candidates does not reserve or assign a therapist.

### ADR-011: Therapist-row locking for collision prevention

**Decision:** Lock every active qualified therapist row in ascending ID order before final availability
recalculation. Insert the appointment on the same PDO connection and commit only after the overlap-safe check.

**Rationale:** MySQL has no general exclusion constraint. Stable parent-row locks serialize both specific
and "any therapist" assignment without relying on an existing appointment row to lock.

## Deferred decisions

- Whether CSS growth justifies an SCSS build step

These should be resolved in the phase that first needs them and recorded here.

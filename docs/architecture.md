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
2. The front controller loads Composer, environment/configuration, shared dependencies, session/security middleware, and route declarations.
3. A minimal router matches HTTP method and path to a controller action.
4. The controller reads request data, delegates validation/use cases, and selects a response or view.
5. Services enforce business rules and coordinate repository calls and transactions.
6. Repositories execute PDO prepared statements and map rows to domain models or purpose-built result objects.
7. Views receive explicit data and render escaped HTML.

The router, request/response helpers, view renderer, environment loader, and dependency wiring will be introduced only as their first use requires them.

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

`config/database.php` currently provides a side-effect-free configuration array. The Phase 2 bootstrap will validate required values and create a single PDO connection with exceptions enabled, native prepares where supported, and associative fetch mode.

## Routing and rendering

Routes will be declared explicitly in `routes/web.php`, grouped conceptually into public and admin routes. Route declarations should remain readable and must not contain business logic. PHP templates are chosen over a template engine to keep dependencies and learning surface small.

## Error handling and logging

- Development may show detailed exceptions; production-facing mode returns generic error pages.
- Exceptions are logged to `storage/logs` without secrets or unnecessary personal data.
- Expected validation failures return field-level feedback and preserve safe input.
- Unexpected failures are handled centrally by the front controller.

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

### ADR-004: One location and one treatment resource

**Decision:** Model a single fictional spa resource rather than staff/resource allocation.

**Rationale:** It preserves the central availability/concurrency problem while avoiding a production-scale scheduling engine. The choice is documented so a later resource model can be introduced deliberately.

### ADR-005: Service snapshots on appointments

**Decision:** Store the booked service name, duration, and price on each appointment in addition to `service_id`.

**Rationale:** Historical bookings remain accurate when catalogue details change. The small, intentional duplication is preferable to misleading history.

### ADR-006: UTC persistence and configured display zone

**Decision:** Persist appointment instants in UTC and convert at boundaries using `APP_TIMEZONE`.

**Rationale:** It makes time handling explicit and avoids dependence on server/database session time zones.

## Deferred decisions

- Exact minimal router implementation and HTTP helper interfaces
- Whether CSS growth justifies an SCSS build step
- Migration runner implementation or selection
- Availability locking strategy after a concurrency-focused prototype

These should be resolved in the phase that first needs them and recorded here.

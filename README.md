# Spa Booking System

A deliberately compact, full-stack portfolio application for browsing spa services and requesting appointments. It demonstrates modern, framework-free PHP and relational database practices without attempting to be a production scheduling platform.

> All names, appointments, customer details, services, prices, and other records in this repository are fictional mock data.

## Planned feature summary

- Public service catalogue with durations and prices
- One fictional spa location with multiple fictional therapists and many-to-many service qualifications
- Customer booking flow with therapist (or "any available therapist"), date/time selection, and confirmation
- Per-therapist availability checks that prevent conflicting appointments
- Admin sign-in and dashboard for reviewing and updating bookings
- Server-side validation, CSRF protection, secure sessions, and prepared SQL
- Responsive, accessible server-rendered pages with light JavaScript enhancement

Phases 1 and 2 provide the framework-free HTTP foundation, therapist-aware MySQL schema, fictional demo
data, and focused PDO repositories. Phase 3 now loads the public services catalogue and service details,
including qualified therapists, from MySQL. A read-only booking entry presents therapist preferences;
date and time-slot selection now use a progressive, position-preserving flow with visible therapist
availability states. Customer details, review, and appointment submission remain planned.

## Technology stack

- PHP 8.3
- MySQL 8
- HTML5
- Plain CSS initially (SCSS can be introduced if the stylesheet warrants a build step)
- Vanilla JavaScript
- PDO with prepared statements
- Composer for PSR-4 autoloading and development tools
- PHPUnit, PHP_CodeSniffer, and PHPStan for automated quality checks

## Local setup

Prerequisites: PHP 8.3 with `pdo_mysql`, MySQL 8, and Composer 2.

```bash
git clone <repository-url>
cd spa-booking-system
composer install
cp .env.example .env
```

Edit `.env` with local database settings. The application uses a small local environment loader and
constructor-based wiring rather than an application container. The public services page opens a database
connection through the PDO factory and reads active services through the service repository.

Start PHP's development server with the public directory as the document root:

```bash
php -S localhost:8000 -t public public/router.php
```

Then visit `http://localhost:8000`; the services page is available at `http://localhost:8000/services`,
with individual active services linked at `/services/{id}`.
The service detail page links to the booking entry at `/book/{serviceId}`.

## Database setup

Create a local database and a least-privileged application user. Example SQL (replace the password):

```sql
CREATE DATABASE spa_booking CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'spa_app'@'localhost' IDENTIFIED BY 'replace-with-local-password';
GRANT SELECT, INSERT, UPDATE, DELETE ON spa_booking.* TO 'spa_app'@'localhost';
FLUSH PRIVILEGES;
```

Copy the matching values into `.env`, then apply the schema and load deterministic fictional demo data:

```bash
composer migrate
composer seed
```

`composer migrate` is safe to rerun and applies only migrations not recorded in the `migrations` ledger.
`composer seed` is also repeatable for the committed demo records. The optional destructive rollback of
the latest migration batch is `composer migrate:rollback`; use it only on a disposable local database.

Schema-management credentials require `CREATE`, `ALTER`, `INDEX`, and `DROP` permissions. Use them only
for migration work and keep the runtime application's account limited to `SELECT`, `INSERT`, `UPDATE`,
and `DELETE`.

MySQL integration tests are opt-in because they create and remove tables. Create an empty, disposable
database named `spa_booking_test`, then use these values in your uncommitted `.env` (keeping the same
host, port, username, password, and charset as your local MySQL setup):

```dotenv
DB_DATABASE=spa_booking
DB_TEST_DATABASE=spa_booking_test
RUN_DATABASE_TESTS=true
```

`DB_TEST_DATABASE` must end in `_test` and must differ from `DB_DATABASE`; the test suite enforces both
rules before it migrates, seeds, and removes its test tables. Run the full integration suite with:

```bash
composer test
```

Set `RUN_DATABASE_TESTS=false` again for the default unit-test run. Without the explicit `true` flag,
the three MySQL integration tests are skipped.

## Demo credentials

The Phase 2 seed creates this fictional administrator for the future Phase 4 sign-in screen:

```text
Email:    admin@example.test
Password: SpaDemo!2026
```

## Screenshots

Screenshots will be added after the customer and admin interfaces exist.

- Customer service catalogue — _placeholder_
- Appointment booking flow — _placeholder_
- Admin dashboard — _placeholder_

## Roadmap

- [x] Phase 1: HTTP foundation, public informational pages, and planning
- [x] Phase 2: therapist-aware database migrations, fictional seed data, and repositories
- [ ] Phase 3: therapist-aware customer catalogue and booking flow
- [ ] Phase 4: admin authentication and appointment dashboard
- [ ] Phase 5: automated testing, accessibility, security review, and polish

Detailed implementation checklists live in [`tasks/`](tasks/), while product and technical decisions live in [`docs/`](docs/).

## Scope note

This project is educational portfolio software for one fictional location, not a production medical, payment, payroll, membership, or healthcare-record system. It does not schedule rooms/equipment, process payments, send real notifications, or store real personal data.

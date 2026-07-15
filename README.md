# Spa Booking System

A deliberately compact, full-stack portfolio application for browsing spa services and requesting appointments. It demonstrates modern, framework-free PHP and relational database practices without attempting to be a production scheduling platform.

> All names, appointments, customer details, services, prices, and other records in this repository are fictional mock data.

## Planned feature summary

- Public service catalogue with durations and prices
- Customer booking flow with date/time selection and confirmation
- Basic availability checks that prevent conflicting appointments
- Admin sign-in and dashboard for reviewing and updating bookings
- Server-side validation, CSRF protection, secure sessions, and prepared SQL
- Responsive, accessible server-rendered pages with light JavaScript enhancement

Only the project foundation is currently scaffolded. Booking and administration features are tracked in the roadmap and `tasks/` documents.

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

Edit `.env` with local database settings. The future bootstrap will load these variables; the initial scaffold intentionally does not include an environment-loader dependency or application container.

Start PHP's development server with the public directory as the document root:

```bash
php -S localhost:8000 -t public
```

Then visit `http://localhost:8000`. At this foundation stage, the front controller returns a placeholder page.

## Database setup

Create a local database and a least-privileged application user. Example SQL (replace the password):

```sql
CREATE DATABASE spa_booking CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'spa_app'@'localhost' IDENTIFIED BY 'replace-with-local-password';
GRANT SELECT, INSERT, UPDATE, DELETE ON spa_booking.* TO 'spa_app'@'localhost';
FLUSH PRIVILEGES;
```

Copy the matching values into `.env`. Migrations and seed execution will be added in Phase 2; see `docs/database-design.md` for the proposed schema. Schema-management credentials may require temporary `CREATE`, `ALTER`, `INDEX`, and `DROP` permissions and should be separate from runtime credentials when practical.

## Demo credentials

Not available yet. Before the admin phase is published, fictional credentials will be documented here:

```text
Email:    demo@example.test
Password: <demo-password-placeholder>
```

## Screenshots

Screenshots will be added after the customer and admin interfaces exist.

- Customer service catalogue — _placeholder_
- Appointment booking flow — _placeholder_
- Admin dashboard — _placeholder_

## Roadmap

- [x] Phase 1: repository foundation, architecture, and planning
- [ ] Phase 2: database migrations, seed data, and PDO infrastructure
- [ ] Phase 3: customer service catalogue and booking flow
- [ ] Phase 4: admin authentication and appointment dashboard
- [ ] Phase 5: automated testing, accessibility, security review, and polish

Detailed implementation checklists live in [`tasks/`](tasks/), while product and technical decisions live in [`docs/`](docs/).

## Scope note

This project is educational portfolio software, not a production medical, payment, or healthcare-record system. It does not process payments, send real notifications, or store real personal data.

# Phase 01: Foundation

## Goal

Create a documented, installable HTTP foundation with public informational pages and quality tooling, without implementing booking behavior or persistence.

## Tasks

- [x] Establish application, public, configuration, database, storage, test, documentation, and task folders.
- [x] Configure Composer PSR-4 autoloading and development quality tools.
- [x] Add environment and database configuration examples without secrets.
- [x] Add validated environment loading and a PDO connection factory without opening a database connection.
- [x] Implement the front controller, explicit router, response object, base controller, view renderer, and centralized error handling.
- [x] Add a shared page layout, responsive navigation/footer, plain CSS, and minimal progressive JavaScript.
- [x] Add the home page and services listing backed by temporary in-memory fictional data.
- [x] Add lightweight automated tests for the router and practical foundation components.
- [x] Document requirements, architecture, data design, flows, and security expectations.
- [x] Document coding conventions, testing commands, and definition of done.
- [ ] Run `composer install` and commit the generated `composer.lock` when dependency installation is authorized.
- [x] Add focused PHPUnit and PHPStan configuration; retain PSR-12 through the Composer lint script.
- [ ] Run `composer check` once PHP 8.3 and Composer are available on the local `PATH`.

## Acceptance criteria

- A new contributor can understand scope and planned architecture from the repository.
- `composer.json` is valid and the PHP scaffold passes syntax checks.
- The web root contains no secrets or non-public application files.
- No booking or admin feature is prematurely implemented.
- Home and services pages are semantic, keyboard accessible, responsive, and usable without JavaScript.
- Known routes return HTML responses, unsupported methods do not match GET routes, and unknown routes return 404.

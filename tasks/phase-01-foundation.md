# Phase 01: Foundation

## Goal

Create a documented, installable project skeleton with clear boundaries and quality tooling, without implementing booking behavior.

## Tasks

- [x] Establish application, public, configuration, database, storage, test, documentation, and task folders.
- [x] Configure Composer PSR-4 autoloading and development quality tools.
- [x] Add environment and database configuration examples without secrets.
- [x] Add a minimal front controller and empty route declaration file.
- [x] Document requirements, architecture, data design, flows, and security expectations.
- [x] Document coding conventions, testing commands, and definition of done.
- [ ] Run `composer install` and commit the generated `composer.lock` when dependency installation is authorized.
- [ ] Configure PHPUnit, PHPStan, and PHP_CodeSniffer only if defaults/scripts prove insufficient.
- [ ] Add the first smoke test alongside the initial router/bootstrap in Phase 2.

## Acceptance criteria

- A new contributor can understand scope and planned architecture from the repository.
- `composer.json` is valid and the PHP scaffold passes syntax checks.
- The web root contains no secrets or non-public application files.
- No booking or admin feature is prematurely implemented.

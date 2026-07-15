# AGENTS.md

This repository is a public portfolio project: a small, framework-free PHP 8.3 application for fictional spa appointment booking. Keep changes understandable to a reviewer and proportional to a demonstration project.

## Coding conventions

- Follow PSR-12 for PHP and use `declare(strict_types=1);` in PHP source files.
- Autoload application classes through Composer under the `SpaBooking\\` namespace.
- Use typed properties, parameter types, and return types where practical.
- Keep controllers thin. Put business rules in services and persistence in repositories.
- Models represent domain data and must not issue SQL or read request globals.
- Prefer dependency injection through constructors over service locators or global state.
- Use `snake_case` for database identifiers, `PascalCase` for PHP classes, `camelCase` for methods and variables, and kebab-case for public asset filenames.
- Escape output in views with `htmlspecialchars(..., ENT_QUOTES, 'UTF-8')`.
- Keep JavaScript framework-free and progressively enhance server-rendered HTML.
- Write accessible, semantic HTML5. Keep CSS organized by component or page rather than relying on inline styles.
- Comments should explain decisions or constraints, not restate the code.

## Folder responsibilities

- `app/Controllers`: translate HTTP requests into service calls and responses.
- `app/Models`: small domain entities and value objects.
- `app/Services`: application use cases and business rules.
- `app/Repositories`: PDO queries and persistence boundaries.
- `app/Validation`: reusable input validation and error collection.
- `app/Views`: PHP templates and partials; no database access.
- `config`: configuration loaders and dependency wiring.
- `database/migrations`: ordered, reversible schema changes.
- `database/seeds`: fictional development/demo data.
- `public`: the web server document root and public assets.
- `routes`: route declarations only.
- `storage/logs`: local runtime logs; log files are not committed.
- `tests`: automated tests mirroring application concerns.
- `docs`: durable product and technical decisions.
- `tasks`: scoped implementation plans and backlog items.

## Security requirements

- Use PDO prepared statements for every value supplied to SQL. Never concatenate user input into SQL.
- Read secrets from environment variables. Never commit `.env`, credentials, or production data.
- Validate input at the application boundary and encode output for its destination.
- Add CSRF protection to every state-changing form before booking/admin features are considered complete.
- Store passwords only with `password_hash()` and verify them with `password_verify()`.
- Regenerate session identifiers after authentication and use secure, HTTP-only, SameSite cookies in deployed environments.
- Enforce authorization server-side for every admin action; hidden UI is not authorization.
- Return generic user-facing errors and avoid logging secrets, session identifiers, passwords, or sensitive customer details.
- Constrain booking dates, service IDs, and other enumerated inputs on the server.
- Use transactions and database constraints for operations that must remain consistent.

## Testing and quality commands

After `composer install`, use:

```bash
composer test
composer lint
composer analyse
composer check
```

For a local syntax check of a single file:

```bash
php -l path/to/file.php
```

## Definition of done

A change is done when:

- Acceptance criteria and relevant security requirements are satisfied.
- Code follows the folder boundaries and conventions above.
- Automated tests cover meaningful success and failure paths.
- `composer check` passes locally.
- Database changes include a migration and, when useful, fictional seed data.
- User-visible behavior is accessible at keyboard and common mobile/desktop sizes.
- No secrets, generated dependencies, logs, or real personal data are committed.
- README, `docs/`, and task status are updated when behavior or decisions change.

## Documentation maintenance

When architecture, dependencies, routes, database structure, security controls, setup steps, or user flows change, update the corresponding file under `docs/` in the same change. Record material architectural decisions and their rationale in `docs/architecture.md`; update `docs/database-design.md` alongside schema changes. If a convention in this file becomes inaccurate, update this file as part of the change.

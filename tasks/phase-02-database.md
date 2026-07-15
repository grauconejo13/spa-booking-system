# Phase 02: Database and application bootstrap

## Goal

Create a repeatable MySQL schema, fictional demo data, and the minimum HTTP/application infrastructure needed by later features.

## Tasks

- [ ] Decide and document the minimal migration runner.
- [ ] Add migrations for `services`, `appointments`, and `admin_users` with constraints and indexes.
- [ ] Add deterministic fictional service, appointment, and admin seeds.
- [ ] Add validated environment loading and configuration bootstrap.
- [ ] Create a PDO connection factory with safe attributes and clear connection errors.
- [ ] Implement the minimal router, request/response handling, view renderer, and centralized error handling.
- [ ] Add repository interfaces only where substitution/testing requires them; avoid generic base repositories.
- [ ] Add database reset/migrate/seed Composer scripts or documented commands.
- [ ] Add integration tests for migrations, seeds, and repository mapping.
- [ ] Update database and architecture documents with final decisions.

## Acceptance criteria

- An empty MySQL 8 database can be migrated and seeded repeatably.
- The runtime account operates with least-privileged grants.
- PDO failures are handled without exposing credentials.
- All committed records use obviously fictional data.

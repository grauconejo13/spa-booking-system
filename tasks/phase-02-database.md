# Phase 02: Database and application bootstrap

## Goal

Create a repeatable therapist-aware MySQL schema, fictional demo data, and persistence infrastructure for later features.

## Tasks

- [ ] Decide and document the minimal migration runner.
- [ ] Add migrations for `services`, `therapists`, `therapist_services`, `therapist_availability`, `appointments`, and `admin_users` with constraints and indexes.
- [ ] Add deterministic fictional services, therapists, qualifications, availability, appointments, and admin seeds.
- [ ] Wire repositories to the Phase 1 PDO connection factory and handle connection errors safely.
- [ ] Add repository interfaces only where substitution/testing requires them; avoid generic base repositories.
- [ ] Add database reset/migrate/seed Composer scripts or documented commands.
- [ ] Add integration tests for migrations, seeds, and repository mapping.
- [ ] Update database and architecture documents with final decisions.

## Acceptance criteria

- An empty MySQL 8 database can be migrated and seeded repeatably.
- The runtime account operates with least-privileged grants.
- PDO failures are handled without exposing credentials.
- All committed records use obviously fictional data.
- The schema supports many-to-many therapist qualifications, per-therapist availability, and per-therapist collision queries.

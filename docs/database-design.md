# Database design

## Design goals

- Keep the schema small and legible.
- Enforce stable invariants with MySQL constraints and indexes.
- Keep SQL visible through PDO repositories.
- Support deterministic fictional seed data.
- Preserve a useful appointment history when catalogue data changes.

## Implemented tables

### `services`

| Column | Type | Notes |
| --- | --- | --- |
| `id` | `BIGINT UNSIGNED` | Primary key, auto increment |
| `name` | `VARCHAR(120)` | Required |
| `slug` | `VARCHAR(140)` | Required, unique, public identifier |
| `description` | `TEXT` | Required |
| `duration_minutes` | `SMALLINT UNSIGNED` | Required, positive |
| `price_cents` | `INT UNSIGNED` | Required; integer avoids floating-point currency errors |
| `is_active` | `BOOLEAN` | Required, default true |
| `display_order` | `SMALLINT UNSIGNED` | Required, default 0 |
| `created_at` | `DATETIME` | UTC |
| `updated_at` | `DATETIME` | UTC |

Indexes: unique `slug`; index on `(is_active, display_order)`.

### `therapists`

| Column | Type | Notes |
| --- | --- | --- |
| `id` | `BIGINT UNSIGNED` | Primary key, auto increment |
| `name` | `VARCHAR(120)` | Required; fictional display name |
| `slug` | `VARCHAR(140)` | Required, unique, public identifier |
| `bio` | `TEXT` | Required, fictional public biography |
| `is_active` | `BOOLEAN` | Required, default true |
| `display_order` | `SMALLINT UNSIGNED` | Required, default 0 |
| `created_at` | `DATETIME` | UTC |
| `updated_at` | `DATETIME` | UTC |

Indexes: unique `slug`; index on `(is_active, display_order)`.

### `therapist_services`

| Column | Type | Notes |
| --- | --- | --- |
| `therapist_id` | `BIGINT UNSIGNED` | Foreign key to `therapists` |
| `service_id` | `BIGINT UNSIGNED` | Foreign key to `services` |
| `created_at` | `DATETIME` | UTC |

Primary key: (`therapist_id`, `service_id`). This join table records qualification only; it does not introduce therapist-specific price or duration.

### `therapist_availability`

| Column | Type | Notes |
| --- | --- | --- |
| `id` | `BIGINT UNSIGNED` | Primary key, auto increment |
| `therapist_id` | `BIGINT UNSIGNED` | Foreign key to `therapists` |
| `day_of_week` | `TINYINT UNSIGNED` | Required; ISO-8601 value 1 (Monday) through 7 (Sunday) |
| `starts_at` | `TIME` | Required local wall-clock start |
| `ends_at` | `TIME` | Required local wall-clock end, greater than start |
| `created_at` | `DATETIME` | UTC |
| `updated_at` | `DATETIME` | UTC |

Indexes: (`therapist_id`, `day_of_week`, `starts_at`, `ends_at`).

### `therapist_availability_exceptions`

| Column | Type | Notes |
| --- | --- | --- |
| `id` | `BIGINT UNSIGNED` | Primary key, auto increment |
| `therapist_id` | `BIGINT UNSIGNED` | Foreign key to `therapists` |
| `exception_date` | `DATE` | Date interpreted in the configured spa timezone |
| `is_available` | `BOOLEAN` | False closes the full date; true defines an override window |
| `starts_at` | `TIME` | Required for available overrides; otherwise null |
| `ends_at` | `TIME` | Required for available overrides; otherwise null |
| `created_at` | `DATETIME` | UTC |
| `updated_at` | `DATETIME` | UTC |

Date exceptions replace recurring windows for that therapist and date. Multiple available rows may
define split override windows; a closed-date row has no start or end time.

### `appointments`

| Column | Type | Notes |
| --- | --- | --- |
| `id` | `BIGINT UNSIGNED` | Primary key, auto increment |
| `reference` | `CHAR(12)` | Random, non-sequential, unique customer-facing reference |
| `service_id` | `BIGINT UNSIGNED` | Foreign key to `services` |
| `therapist_id` | `BIGINT UNSIGNED` | Foreign key to the assigned `therapists` row |
| `service_name` | `VARCHAR(120)` | Snapshot at booking time |
| `duration_minutes` | `SMALLINT UNSIGNED` | Snapshot at booking time |
| `price_cents` | `INT UNSIGNED` | Snapshot at booking time |
| `customer_name` | `VARCHAR(120)` | Required |
| `customer_email` | `VARCHAR(254)` | Required, normalized for contact/search |
| `customer_phone` | `VARCHAR(32)` | Nullable |
| `customer_note` | `VARCHAR(1000)` | Nullable, plain text only |
| `starts_at` | `DATETIME` | UTC, required |
| `ends_at` | `DATETIME` | UTC, required and greater than start |
| `status` | `ENUM(...)` or `VARCHAR(20)` | `pending`, `confirmed`, `completed`, `cancelled` |
| `created_at` | `DATETIME` | UTC |
| `updated_at` | `DATETIME` | UTC |

Indexes: unique `reference`; `(therapist_id, starts_at, ends_at, status)` for availability; `(status, starts_at)` for admin lists; `customer_email` for admin search if that feature is retained.

MySQL cannot express a general non-overlap exclusion constraint. The booking service locks active qualified
therapist rows in stable ID order, then checks blocking appointments and inserts on the same transaction. This
serializes competing requests even when no appointment row exists yet. The rule is:

```sql
existing.starts_at < requested_end
AND existing.ends_at > requested_start
AND existing.therapist_id = requested_therapist_id
AND existing.status IN ('pending', 'confirmed')
```

### `admin_users`

| Column | Type | Notes |
| --- | --- | --- |
| `id` | `BIGINT UNSIGNED` | Primary key, auto increment |
| `name` | `VARCHAR(120)` | Required |
| `email` | `VARCHAR(254)` | Required, unique, normalized |
| `password_hash` | `VARCHAR(255)` | Output from PHP `password_hash()` |
| `is_active` | `BOOLEAN` | Required, default true |
| `last_login_at` | `DATETIME` | Nullable, UTC |
| `created_at` | `DATETIME` | UTC |
| `updated_at` | `DATETIME` | UTC |

Index: unique `email`.

## Relationships

```text
services (many) -- therapist_services -- (many) therapists
therapists (1) ----- (many) therapist_availability
services (1) ------- (many) appointments
therapists (1) ----- (many) appointments

admin_users is independent; session state is stored by PHP for the initial scope.
```

Use `RESTRICT` for deleting a service or therapist referenced by appointments. Catalogue/staff removal should normally set `is_active = false`, preserving history and referential integrity. Deleting a service or therapist may cascade its join/availability rows only when no appointment reference prevents the parent deletion.

## Status transitions

- `pending` -> `confirmed` or `cancelled`
- `confirmed` -> `completed` or `cancelled`
- `completed` and `cancelled` are terminal in the initial release

Transitions are enforced in the service layer. The database limits status values but does not encode the state machine.

## Migrations

- Migration files use timestamped names and return an object with explicit `up`/`down` behavior.
- Each schema change is forward-applicable to an empty database.
- Applied files are recorded by filename and batch in the `migrations` table. `composer migrate` applies only pending files.
- `composer migrate:rollback` reverses only the latest batch. It is destructive, clearly labeled, and must never be run automatically in shared environments.
- MySQL DDL implicitly commits. The runner records a migration after `up` completes, but cannot promise transactional rollback of partially executed DDL; inspect a failed schema before retrying.
- Runtime application credentials remain least-privileged; migration credentials may be separate.

## Seeds and data policy

`composer seed` creates three services, three therapists, overlapping qualifications, weekday availability, two future appointments, and one demo administrator. Stable identifiers and fictional values make reruns deterministic; the administrator password hash is safely regenerated with `password_hash()` on each run. All email addresses use the reserved `.test` domain. Never copy production or real customer data into seeds, tests, screenshots, or commits.

## Integration-test isolation

Database integration tests are opt-in. They require `RUN_DATABASE_TESTS=true` and a `DB_TEST_DATABASE` value ending in `_test`. The database must be empty, must be disposable, and must be different from the development database. The suite migrates, seeds, checks repeatability and repository mapping, then rolls the schema back.

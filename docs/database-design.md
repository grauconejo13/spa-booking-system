# Database design

## Design goals

- Keep the schema small and legible.
- Enforce stable invariants with MySQL constraints and indexes.
- Keep SQL visible through PDO repositories.
- Support deterministic fictional seed data.
- Preserve a useful appointment history when catalogue data changes.

## Proposed tables

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

### `appointments`

| Column | Type | Notes |
| --- | --- | --- |
| `id` | `BIGINT UNSIGNED` | Primary key, auto increment |
| `reference` | `CHAR(12)` | Random, non-sequential, unique customer-facing reference |
| `service_id` | `BIGINT UNSIGNED` | Foreign key to `services` |
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

Indexes: unique `reference`; `(starts_at, ends_at, status)` for availability; `(status, starts_at)` for admin lists; `customer_email` for admin search if that feature is retained.

MySQL cannot express a general non-overlap exclusion constraint. The booking service will check for overlapping blocking appointments inside a transaction. The final locking/isolation approach must be verified with concurrent integration tests in Phase 3. The rule is:

```sql
existing.starts_at < requested_end
AND existing.ends_at > requested_start
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
services (1) -------- (many) appointments

admin_users is independent; session state is stored by PHP for the initial scope.
```

Use `RESTRICT` for deleting a service referenced by appointments. Catalogue removal should normally set `is_active = false`, preserving history and referential integrity.

## Status transitions

- `pending` -> `confirmed` or `cancelled`
- `confirmed` -> `completed` or `cancelled`
- `completed` and `cancelled` are terminal in the initial release

Transitions are enforced in the service layer. The database limits status values but does not encode the state machine.

## Migrations

- Migration files use timestamped names and contain explicit `up`/`down` behavior or paired SQL sections.
- Each schema change is forward-applicable to an empty database.
- Destructive rollback behavior must be clearly labeled and never run automatically in shared environments.
- Runtime application credentials remain least-privileged; migration credentials may be separate.

## Seeds and data policy

Seeds will create a small service catalogue, several appointments across statuses, and one demo administrator. All values must be unmistakably fictional; use reserved `.test` email addresses. Never copy production or real customer data into seeds, tests, screenshots, or commits.

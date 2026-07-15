# Security notes

This is a portfolio application using fictional data, but it should demonstrate production-minded controls. Security controls are implementation requirements, not claims about the current foundation scaffold.

## Threat boundaries

Treat all request values, route parameters, headers, cookies, seed imports, and database strings rendered into HTML as untrusted. The public booking form is anonymous; admin routes cross an authentication and authorization boundary.

## Required controls

### Database

- Use PDO prepared statements for values and allowlist any dynamic column, direction, or identifier.
- Configure `PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION`, `PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC`, and native prepares when compatible with the query.
- Give runtime credentials only `SELECT`, `INSERT`, `UPDATE`, and `DELETE` access to the application schema.
- Use constraints, transactions, and tested concurrency handling for therapist assignment and booking consistency.

### Input and output

- Validate type, length, format, membership, and business rules on the server.
- Canonicalize email/date input deliberately; never “sanitize” invalid data into surprising valid data.
- Escape HTML at render time with UTF-8-safe contextual encoding.
- Do not place customer email, phone, note, or predictable appointment IDs in URLs.
- Emit JSON only through a JSON encoder if an endpoint is later introduced.

### Sessions and authentication

- Use `password_hash()` with the current recommended PHP default and `password_verify()`.
- Regenerate the session ID after sign-in and privilege changes.
- Set cookies `HttpOnly`, `SameSite=Lax` (or stricter where usable), `Secure` under HTTPS, and an appropriate path.
- Enforce idle/absolute timeouts and invalidate sessions on sign-out.
- Use a generic login failure message and add rate limiting/backoff before public deployment.
- Require authentication and authorization on every admin request.

### CSRF and request methods

- Generate cryptographically random, session-bound CSRF tokens and compare with `hash_equals()`.
- Require tokens on booking, sign-in, sign-out, and all admin mutations.
- Use POST for state changes; reject unsupported methods.

### Errors, logging, and privacy

- Disable detailed error display outside development.
- Log useful event context without passwords, full session IDs, CSRF tokens, database credentials, or unnecessary customer data.
- Keep log files outside public paths and out of Git.
- Collect only the fields required for the demo; do not request health, payment, or real personal data.
- Use only clearly fictional records in tests, seeds, screenshots, and documentation.

### Browser and deployment defenses

- Deploy behind HTTPS and configure HSTS at the web server when appropriate.
- Add a restrictive Content Security Policy once asset needs are known, plus `X-Content-Type-Options: nosniff`, a suitable `Referrer-Policy`, and frame protections.
- Keep `public/` as the document root so source, configuration, storage, and dependencies are not web-accessible.
- Pin Composer dependencies through `composer.lock` once installed; run `composer audit` in routine checks.

## Verification checklist

- SQL injection attempts remain data and do not alter query structure.
- Stored/reflected HTML is encoded in every view context.
- Missing/invalid CSRF tokens fail every state-changing request.
- Anonymous users cannot reach admin data or actions.
- Session IDs change at login and are invalid after logout.
- Unqualified or unavailable therapists are rejected, and per-therapist booking collisions are rejected under concurrent requests.
- Errors do not expose stack traces or configuration in production mode.
- Dependencies and PHP packages report no known advisories at release time.

# Phase 05: Testing and polish

## Goal

Turn the completed MVP into a clear, reproducible, secure, and presentable portfolio project.

## Tasks

- [ ] Raise unit/integration coverage around validation, therapist qualification/availability, per-therapist booking transactions, auth, and status rules.
- [ ] Add end-to-end smoke coverage for the customer and admin happy paths.
- [ ] Run PHP_CodeSniffer, PHPStan, PHPUnit, Composer validation, and security audit in CI.
- [ ] Review keyboard navigation, focus states, labels, error association, contrast, and responsive layouts.
- [ ] Review SQL injection, XSS, CSRF, session, authorization, error, and logging controls.
- [ ] Add production-mode security headers and confirm `public/` document-root deployment.
- [ ] Test clean setup from README instructions on a fresh environment.
- [ ] Add representative screenshots using only fictional data.
- [ ] Add a concise architecture diagram and update all final implementation decisions.
- [ ] Remove dead code, debug output, stale TODOs, and placeholder content.

## Acceptance criteria

- `composer check` and the documented database integration suite pass on a clean checkout.
- A manual accessibility and security checklist is completed with findings resolved or documented.
- README setup, credentials, screenshots, feature list, and roadmap match the shipped application.
- No real data, secrets, logs, or environment files are present in Git.

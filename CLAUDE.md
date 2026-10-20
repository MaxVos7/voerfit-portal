@AGENTS.md

# voerfit-portal

Customer portal for the Voerfit farm platform, built with **Laravel**, **Inertia**, **Vue 3** (TypeScript), **Pest** and **Pint**.

## Test-driven development

We work test-first. For every feature or bug fix:

1. **Red** — write a Pest test that describes the desired behaviour and run it to confirm it fails for the expected reason.
2. **Green** — write the minimal code needed to make that test pass.
3. **Refactor** — clean up while keeping the tests green.

Rules:

- No production code without a failing test that requires it.
- A bug fix starts with a test that reproduces the bug.
- Prefer feature tests (HTTP requests through Inertia responses, e.g. `assertInertia`) for behaviour; use unit tests for isolated logic.
- Pure copy, styling and layout-only changes are the exception and don't need a test first.
- Run only the relevant tests while iterating: `php artisan test --compact --filter=...`.
- Before finishing a task, run the full suite and format the code:
  - `vendor/bin/pint --dirty --format agent` — fix code style
  - `composer test` — Pint check, PHPStan and the full Pest suite
  - `npm run check` and `npm run types:check` — frontend lint and type checks

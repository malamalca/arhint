# Arhint4 — CakePHP 5 Intranet App

## Stack
- **Framework:** CakePHP 5.x (PHP 8.1+)
- **Core plugins:** `Crm`, `Expenses`, `Documents`, `Projects`, `Tasks`, `Calendar`
- **External libs:** phpoffice/phpspreadsheet, tcpdf, sabre/dav, syncroton, enqueue/fs, genkgo/camt
- **AI integrations:** AIAssistant service, EmbeddingService, Qdrant vector DB (live tests)

## Architecture
- Controllers should remain thin and coordinate requests/responses.
- Business logic belongs in services under `Lib/`.
- ORM queries belong in Table classes.
- Shared cross-entity logic should be implemented as Behaviors.
- Prefer extending existing patterns before introducing new abstractions.

## Directory Layout
src/                          — App namespace (core)
  Controller/                 — AppController + app-wide controllers
  Model/Table/                — ORM table classes
  Model/Behavior/             — Shared behaviors
  Lib/                        — Services, helpers, factories
  Job/                        — Queue jobs (enqueue)
  Event/                      — Application event listeners
  Filter/                     — Middleware filters
  Policy/                     — Authorization policies
plugins/<Name>/               — Each plugin mirrors src/ structure
tests/TestCase/               — App tests
tests/Fixture/                — App fixtures

## PSR-4 Namespaces
- `App\` → `src/`
- `Crm\` → `plugins/Crm/src/`
- `Expenses\` → `plugins/Expenses/src/`
- `Documents\` → `plugins/Documents/src/`
- `Projects\` → `plugins/Projects/src/`
- `Tasks\` → `plugins/Tasks/src/`
- `Calendar\` → `plugins/Calendar/src/`

## Before Making Changes
1. Read the relevant controller, table, service, and tests first.
2. Search for existing implementations before creating new patterns.
3. Prefer modifying existing services/behaviors over introducing new abstractions.
4. Keep changes within the owning plugin whenever possible.
5. Run targeted tests for modified code before proposing completion.

## Commands
- Run all tests: `vendor/bin/phpunit`
- Single test file: `vendor/bin/phpunit tests/TestCase/Lib/LilTest.php`
- Single test method: `vendor/bin/phpunit --filter=testMethodX tests/TestCase/Lib/LilTest.php`
- Stop on first failure: `vendor/bin/phpunit --stop-on-failure`
- Static analysis: `vendor/bin/phpstan analyse`
- Code style check: `vendor/bin/phpcs`
- Code style fix: `vendor/bin/phpcbf`
- Psalm type check: `vendor/bin/psalm`

## Testing Expectations
- New business logic should include tests when practical.
- Bug fixes should include a regression test when feasible.
- Prefer unit/integration tests over LiveTests.
- Use fixtures rather than external dependencies.

## PHPUnit Runtime Notes
- Full suite typically takes ~9 minutes. Use a 10-minute timeout guard.
- If execution exceeds expectations:
  1. Run `vendor/bin/phpunit --stop-on-failure`
  2. Narrow scope with `--filter`
  3. Skip `*LiveTest.php` files for fast feedback
- Never kill a run before checking the latest output line.

## Plugin Rules
- Prefer modifying code inside the owning plugin.
- Avoid introducing new cross-plugin dependencies.
- Reuse existing plugin services before creating new ones.
- Keep namespaces and responsibilities isolated.

## AI Services
- Avoid live AI calls in normal test runs.
- Mock AI integrations whenever possible.
- Live integration tests belong in `*LiveTest.php`.
- Keep vector database access behind service classes.

## Database
- MySQL/MariaDB with UUID primary keys.
- Test DB configured through `phpunit.xml.dist` → `tests/bootstrap.php`.
- Use migrations for schema changes.
- Update fixtures when schema changes affect tests.
- Check for duplicate PK conflicts after failed test runs.

## Conventions
- Controllers extend `App\Controller\AppController`
- Table classes live under `Model\Table\`
- Behaviors live under `Model\Behavior\`
- Follow CakePHP Coding Standard (PSR-12 + CakePHP sniffs)
- Authorization policies live in `Policy/`
- Queue jobs implement `execute()` and return enqueue results (`ack` / `reject`)

## Avoid
- Do not bypass authorization policies.
- Do not duplicate existing service logic.
- Do not introduce direct SQL when ORM solutions are appropriate.
- Do not modify LiveTests unless required.
- Do not introduce new frameworks or architectural patterns without justification.

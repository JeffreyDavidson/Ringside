# GitHub Actions & CI/CD

## Current Workflow Configuration

The project uses five automated workflows:

### 1. **CI Pipeline** (`.github/workflows/ci.yml`, workflow name "Application Quality")
**Trigger**: Pushes to `develop` or `main`, and pull requests targeting `develop` or `main`. Pushing a feature branch on its own does **not** run this workflow; open a pull request to get CI feedback. A newer run for the same pull request or ref cancels the one in progress.
**Purpose**: Comprehensive testing and static analysis

**Jobs** (all eleven run independently in parallel on `ubuntu-latest`; none uses `needs`):

Production runs **MySQL 8**. The application supports MySQL, PostgreSQL, and SQLite, and CI runs the non-browser suite on all three: in-memory SQLite (`application-tests`, `coverage`), PostgreSQL 17 (`postgres-tests`), and MySQL 8.0 (`mysql-tests`). SQLite ignores row locks (`lockForUpdate()`), has no case-insensitive collation, and is not the production engine, so the two server jobs are the ones that prove locking SQL, generated-column indexes, and engine-specific migration branches.

| Job | Check name | What it runs |
| --- | --- | --- |
| `dependency-validation` | Dependency validation | `composer validate --strict --no-check-publish`, `composer check-platform-reqs --no-dev`, `composer why-not php 8.5 --locked`, `composer audit --locked`, then `npm audit --audit-level=high` (up to three attempts) |
| `lint` | PHP and Blade formatting | `composer test:lint` |
| `rector` | Rector | `composer test:rector` |
| `static-analysis` | PHPStan static analysis | `composer test:types` (application and Pest test configurations) |
| `type-coverage` | Pest type coverage | `composer test:type-coverage` (100% minimum) |
| `frontend-verification` | Frontend verification | `npm run lint`, `npm run build` |
| `application-tests` | `CI - PHP-8.5 - Laravel-13.*` | Pest with the `Browser` suite excluded (Feature, Integration, and Unit run), in parallel |
| `postgres-tests` | Postgres tests | Pest with the `Browser` suite excluded (Unit, Feature, Integration), non-parallel and without coverage, against a `postgres:17` service container. Job-level `DB_*` environment variables override the SQLite settings in `.env.testing` and `phpunit.xml`. A second step runs the opt-in `postgres-concurrency` group (`RUN_CONCURRENCY_TESTS=1`), which books matches from two real processes to prove there are no lock-order deadlocks; see `docs/testing/postgres-concurrency-tests.md` |
| `mysql-tests` | MySQL tests | The same non-browser suite, non-parallel and without coverage, against a `mysql:8.0` service container (the production engine), with job-level `DB_*` variables selecting it. Tests that only apply to another engine are skipped with an explicit reason. The concurrency group is not run here: its harness is PostgreSQL-only |
| `coverage` | Coverage (100%) | `composer test:coverage`: non-parallel Pest run with PCOV, Browser suite excluded, fails below 100% |
| `browser-tests` | Browser Tests | Installs Chromium, builds assets, then `composer test:browser` |

**Key Features:**
- **Runtime**: PHP 8.5 (shared `.github/actions/setup-php-composer` action) and Node.js 24 for jobs that need it. `package.json` declares `engines.node: ">=22.13"`, the lowest version the toolchain supports (Vite 8 and laravel-vite-plugin 3 need 22.12, ESLint 10 needs 22.13), so a server still on Node 22 deploys without `EBADENGINE` warnings; Node 24 remains the recommended version everywhere
- **Impacted Tests First**: `application-tests` runs `pest --tia --baselined --filtered` and falls back to the full non-browser suite with a warning if TIA is unavailable or fails
- **Parallel Test Execution**: `application-tests` uses `--parallel` for speed. The `coverage` job deliberately does not (see Coverage policy below)
- **Memory Limit**: 4G for PHP (`memory_limit=4G` in the setup action, `-d memory_limit=4G` for Pest, `--memory-limit=4G` for PHPStan)
- **Dependency Caching**: The Composer download cache (keyed on `composer.lock`) and the npm cache (keyed on `package-lock.json`). PHPStan result caches are **not** cached between CI runs.
- **Browser Tests**: Not gated. `browser-tests` runs on every trigger above, and failure screenshots are uploaded as the `pest-browser-screenshots` artifact for 7 days
- **Test Environment**: `type-coverage`, `application-tests`, `postgres-tests`, `mysql-tests`, `coverage`, and `browser-tests` (as well as the Coverage and TIA Baseline workflows) run `cp .env.testing .env`, `php artisan key:generate`, and `php artisan config:cache` on the disposable runner

### 2. **Security Scan** (`.github/workflows/security-scan.yml`, workflow name "Security")
**Trigger**: Pull requests targeting `develop` or `main`, a weekly schedule (Mondays 09:00 UTC), and manual dispatch. It does not run on pushes.
**Purpose**: Ward security scan (`Ward (Advisory)` job, `continue-on-error: true`, so it does not block merges). Pull requests are scanned against a baseline from the base commit and fail on new high findings; scheduled and manual runs fail on any high finding. Reports are uploaded as `ward-security-reports` and SARIF is uploaded to GitHub code scanning.

### 3. **TIA Baseline** (`.github/workflows/tia-baseline.yml`, workflow name "Pest TIA Baseline")
**Trigger**: Pushes to `develop`, a daily schedule (03:00 UTC), and manual dispatch
**Purpose**: Record the Pest Test Impact Analysis dependency graph (`pest --ci --tia --fresh --parallel`, Browser suite included) and upload it as the `pest-tia-baseline` artifact for 30 days. `application-tests` in the CI pipeline uses this baseline.

### 4. **Coverage Testing** (`.github/workflows/coverage.yml`, workflow name "Code Coverage")
**Trigger**: Manual dispatch only
**Purpose**: Generate PCOV coverage for the Feature, Integration, and Unit suites (`--min=100`, non-parallel), upload `coverage.xml` as the `pest-coverage-report` artifact for 14 days, and upload it to Codecov (the run fails if the Codecov upload fails)

### 5. **Unordered Selects** (`.github/workflows/unordered-selects.yml`, workflow name "Unordered Selects")
**Trigger**: A daily schedule (03:30 UTC, after the TIA baseline) and manual dispatch; it checks out `develop` explicitly because scheduled runs start on the default branch
**Purpose**: Run the full application suite (Browser excluded, SQLite, `--parallel`) with `REVERSE_UNORDERED_SELECTS=1` so hidden row-order assumptions fail. A red run emails the repository's default GitHub notification; there is no other alert.

### Coverage policy (100%)
The `coverage` job in `ci.yml` runs `composer test:coverage` on every pull request and push to `develop`/`main`. `phpunit.xml` has no `<source><exclude>` entries, so all of `app/` (Livewire, Console, and `AppServiceProvider` included) must be fully covered. The Browser suite is excluded from the gate because ordinary tests already cover what it reaches.

The gate must run **without** `--parallel`: merged parallel-worker coverage drops some `match` header lines (the arms are covered, the header statement is lost), and which lines are dropped varies between runs. The non-parallel result was verified identical across repeated runs (6809 of 6809 statements). Do not use `@codeCoverageIgnore`; delete unreachable code or test it through its behavior. Locally the run takes about 2 minutes.

## Branch Protection Integration

Branch protection is configured in GitHub repository settings and is **not verifiable from this repository**. Check *Settings > Branches* before relying on any specific rule.

What the repository does define:
- The project convention (see `AGENTS.md`) is that `develop` is the integration branch and `main` is the release branch, and that changes reach them through pull requests
- The `application-tests` job is named `CI - PHP-8.5 - Laravel-13.*`. If that name is configured as a required status check, renaming the job in `ci.yml` also requires updating the setting
- CI and the security scan run for pull requests targeting `develop` or `main`, so those runs are what any required checks report against

```bash
# ⚠️ Pushing a feature branch alone does not start CI
git push origin chore/update-documentation

# ✅ Opening a PR to develop starts CI and the security scan
gh pr create --base develop

# ❌ Direct commits to develop or main are against project convention
git push origin develop
```

## Dependency Updates

`.github/dependabot.yml` opens weekly (Monday, 06:00 America/New_York) update PRs against `develop` for
Composer, npm, and GitHub Actions (the workflows and the local `setup-php-composer` action).

- Minor and patch updates are grouped per ecosystem; major updates arrive as individual PRs.
- Titles follow Conventional Commits (`chore(deps)`, `chore(deps-dev)`, `ci(deps)`).
- Nothing merges automatically. Each update PR runs the normal required checks (including Dependency
  validation, Coverage, and Postgres tests) and still needs a human merge; dependency changes need
  approval per `AGENTS.md`.
- Two lockfile advisories blocked every PR within one week in September 2026 (npm `brace-expansion`,
  Composer `league/commonmark`). Enable **Dependabot security updates** in the repository settings to get
  a PR as soon as an advisory is published, instead of waiting for the weekly run.

## Local Git Hooks

`npm install` runs `git config core.hooksPath .githooks` (the `prepare` script), which enables:
- **`pre-commit`**: fast checks only. It runs `php -l` and Pint (`--blade --test`) on staged PHP files, and ESLint and Prettier on staged JavaScript files
- **`pre-push`**: runs `composer test:push`. It first runs `composer test:static`, the four static checks (type coverage, Rector, lint and PHPStan) side by side through `concurrently`, failing if any of them fails and printing each check's output as one block. Then it runs the application tests, rebuilds the frontend assets with `npm run build` (a stale `public/build` has made browser tests fail for reasons unrelated to the change) and runs the browser tests. Set `SKIP_PRE_PUSH_CHECKS=1` to skip it deliberately

## Checking for Hidden Row-Order Assumptions

A query without `ORDER BY` returns rows in whatever order the engine finds convenient. SQLite and MySQL usually return primary key order, so a test that relies on it passes locally and in CI, while PostgreSQL may return another order. Run the suite once with SQLite's `reverse_unordered_selects` pragma to expose these assumptions:

```bash
REVERSE_UNORDERED_SELECTS=1 composer test:application
```

`tests/Pest.php` turns the pragma on for every Feature and Integration test when the variable is `1` and the suite runs on SQLite (it is ignored on PostgreSQL and MySQL). Every unordered result then comes back reversed, so a test that depended on it fails. The fix is an explicit `ORDER BY` in the application when the order is shown to users or drives locking, or `toEqualCanonicalizing()` in the test when the order is not part of the contract. The pull request pipeline does not run it, but the Unordered Selects workflow (`unordered-selects.yml`) runs it nightly against `develop`; run it locally after changing queries that return lists.

## Troubleshooting Common Issues

### **CI Workflow Failures**

**Test Failures:**
```bash
# Check specific test suite locally
./vendor/bin/pest --testsuite=Feature --stop-on-failure
./vendor/bin/pest --testsuite=Integration --stop-on-failure
./vendor/bin/pest --testsuite=Unit --stop-on-failure

# Run the same non-browser suite CI runs
composer test:application

# Run the browser suite (requires Playwright Chromium and built assets)
npm run build
composer test:browser
```

Do not copy `.env.testing` over `.env` locally, since that overwrites your development configuration. Pest runs with `APP_ENV=testing` (set in `phpunit.xml`), and Laravel then loads `.env.testing` in place of `.env`. The `phpunit.xml` `<env>` values also apply. If tests seem to ignore these settings, run `php artisan config:clear`, because cached configuration bypasses environment files. CI copies the file only because its runner is disposable.

**PHPStan Errors:**
```bash
# Run PHPStan locally with same settings
composer test:types

# Clear PHPStan cache if needed
./vendor/bin/phpstan clear-result-cache
```

Locally, PHPStan caches results under `storage/framework/cache/` (`phpstan-app` and `phpstan-pest`). CI does not restore that cache.

**Memory Issues:**
- CI uses a 4G memory limit
- The Composer test scripts already pass `4G`. For a direct run use: `php -d memory_limit=4G vendor/bin/pest`

### **Code Styling Issues**

Run the repository's Composer lint script locally:
```bash
composer lint
composer test:lint
```

### **Coverage Workflow Issues**

**Coverage Not Generating:**
```bash
# Run coverage locally (needs PCOV or Xdebug; see docs/testing/troubleshooting.md for Herd)
composer test:coverage

# Generate a local coverage report with the project minimum
./vendor/bin/pest --exclude-testsuite Browser --coverage --min=100 --coverage-clover=coverage.xml
```

**Codecov Upload Failures:**
- Check `CODECOV_TOKEN` secret is set in GitHub repository
- Verify coverage.xml file is generated successfully

## Environment Configuration

### **Test Environment** (`.env.testing`)
```env
# Performance optimizations for tests
DB_CONNECTION=sqlite
DB_DATABASE=:memory:          # SQLite in-memory database
CACHE_STORE=array            # Array-based cache (fastest)
SESSION_DRIVER=array         # Array-based sessions
QUEUE_CONNECTION=sync        # Synchronous queue processing
MAIL_MAILER=array           # Array mail driver (no emails sent)
LOG_CHANNEL=null             # Discard log output
```

**Why These Settings:**
- **Memory DB**: Fastest database operations for tests
- **Array Drivers**: Eliminate I/O operations for cache/sessions
- **Sync Queue**: Immediate job processing in tests
- **Null Log Channel**: Tests do not write `storage/logs/laravel.log` (it used to grow by about 4 MB per run). `phpunit.xml` sets it for local runs; `.env.testing` sets it too because CI caches config from a copy of that file, which `phpunit.xml` cannot override. No test asserts on log output; use `Log::spy()` or a fake in the test itself if one ever needs to
- **Application Key**: `.env.testing` includes an `APP_KEY` for local test runs. CI copies the file and runs `php artisan key:generate`, so CI runs use a freshly generated key

## Workflow Best Practices

### **For Feature Development:**
1. **Create a conventional branch** - for example `feat/new-feature` or `chore/update-documentation`
2. **Run the local checks** - use `composer lint` and the affected test commands
3. **Open a pull request** - CI runs on pull requests targeting `develop` or `main`, not on feature-branch pushes alone
4. **Check CI status** - ensure all checks pass before merging

### **For Merges:**
1. **Create PR** - target `develop` for normal work
2. **Ensure CI checks pass** - the ten `Application Quality` jobs run for every pull request
3. **Run coverage when needed** - dispatch `coverage.yml` for a PCOV report
4. **Merge when green** - verify the head branch, base branch, and merge method first

### **Local Development Tips:**
```bash
# Run the full local verification suite (what the pre-push hook runs)
composer test:push

# Check types like CI does
composer test:types

# Verify styling before push
composer test:lint
```

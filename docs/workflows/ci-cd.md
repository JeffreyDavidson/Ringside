# GitHub Actions & CI/CD

## Current Workflow Configuration

The project uses four automated workflows:

### 1. **CI Pipeline** (`.github/workflows/ci.yml`, workflow name "Application Quality")
**Trigger**: Pushes to `develop` or `main`, and pull requests targeting `develop` or `main`. Pushing a feature branch on its own does **not** run this workflow; open a pull request to get CI feedback. A newer run for the same pull request or ref cancels the one in progress.
**Purpose**: Comprehensive testing and static analysis

**Jobs** (all eight run independently in parallel on `ubuntu-latest`; none uses `needs`):

| Job | Check name | What it runs |
| --- | --- | --- |
| `dependency-validation` | Dependency validation | `composer validate --strict --no-check-publish`, `composer check-platform-reqs --no-dev`, `composer why-not php 8.5 --locked`, `composer audit --locked`, then `npm audit --audit-level=high` (up to three attempts) |
| `lint` | PHP and Blade formatting | `composer test:lint` |
| `rector` | Rector | `composer test:rector` |
| `static-analysis` | PHPStan static analysis | `composer test:types` (application and Pest test configurations) |
| `type-coverage` | Pest type coverage | `composer test:type-coverage` (100% minimum) |
| `frontend-verification` | Frontend verification | `npm run lint`, `npm run build` |
| `application-tests` | `CI - PHP-8.5 - Laravel-13.*` | Pest with the `Browser` suite excluded (Feature, Integration, and Unit run), in parallel |
| `browser-tests` | Browser Tests | Installs Chromium, builds assets, then `composer test:browser` |

**Key Features:**
- **Runtime**: PHP 8.5 (shared `.github/actions/setup-php-composer` action) and Node.js 24 for jobs that need it
- **Impacted Tests First**: `application-tests` runs `pest --tia --baselined --filtered` and falls back to the full non-browser suite with a warning if TIA is unavailable or fails
- **Parallel Test Execution**: `--parallel` flag for faster test runs
- **Memory Limit**: 4G for PHP (`memory_limit=4G` in the setup action, `-d memory_limit=4G` for Pest, `--memory-limit=4G` for PHPStan)
- **Dependency Caching**: The Composer download cache (keyed on `composer.lock`) and the npm cache (keyed on `package-lock.json`). PHPStan result caches are **not** cached between CI runs.
- **Browser Tests**: Not gated. `browser-tests` runs on every trigger above, and failure screenshots are uploaded as the `pest-browser-screenshots` artifact for 7 days
- **Test Environment**: `type-coverage`, `application-tests`, and `browser-tests` (as well as the Coverage and TIA Baseline workflows) run `cp .env.testing .env`, `php artisan key:generate`, and `php artisan config:cache` on the disposable runner

### 2. **Security Scan** (`.github/workflows/security-scan.yml`, workflow name "Security")
**Trigger**: Pull requests targeting `develop` or `main`, a weekly schedule (Mondays 09:00 UTC), and manual dispatch. It does not run on pushes.
**Purpose**: Ward security scan (`Ward (Advisory)` job, `continue-on-error: true`, so it does not block merges). Pull requests are scanned against a baseline from the base commit and fail on new high findings; scheduled and manual runs fail on any high finding. Reports are uploaded as `ward-security-reports` and SARIF is uploaded to GitHub code scanning.

### 3. **TIA Baseline** (`.github/workflows/tia-baseline.yml`, workflow name "Pest TIA Baseline")
**Trigger**: Pushes to `develop`, a daily schedule (03:00 UTC), and manual dispatch
**Purpose**: Record the Pest Test Impact Analysis dependency graph (`pest --ci --tia --fresh --parallel`, Browser suite included) and upload it as the `pest-tia-baseline` artifact for 30 days. `application-tests` in the CI pipeline uses this baseline.

### 4. **Coverage Testing** (`.github/workflows/coverage.yml`, workflow name "Code Coverage")
**Trigger**: Manual dispatch only
**Purpose**: Generate PCOV coverage for the Feature, Integration, and Unit suites (`--min=44`), upload `coverage.xml` as the `pest-coverage-report` artifact for 14 days, and upload it to Codecov (the run fails if the Codecov upload fails)

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

## Local Git Hooks

`npm install` runs `git config core.hooksPath .githooks` (the `prepare` script), which enables:
- **`pre-commit`**: fast checks only. It runs `php -l` and Pint (`--blade --test`) on staged PHP files, and ESLint and Prettier on staged JavaScript files
- **`pre-push`**: runs `composer test:push` (type coverage, Rector, lint, PHPStan, application tests, browser tests). Set `SKIP_PRE_PUSH_CHECKS=1` to skip it deliberately

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
# Run coverage locally (needs PCOV or Xdebug)
composer test:coverage

# Generate a local coverage report with the project minimum
./vendor/bin/pest --coverage --min=44 --coverage-clover=coverage.xml
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
```

**Why These Settings:**
- **Memory DB**: Fastest database operations for tests
- **Array Drivers**: Eliminate I/O operations for cache/sessions
- **Sync Queue**: Immediate job processing in tests
- **Application Key**: `.env.testing` includes an `APP_KEY` for local test runs. CI copies the file and runs `php artisan key:generate`, so CI runs use a freshly generated key

## Workflow Best Practices

### **For Feature Development:**
1. **Create a conventional branch** - for example `feat/new-feature` or `chore/update-documentation`
2. **Run the local checks** - use `composer lint` and the affected test commands
3. **Open a pull request** - CI runs on pull requests targeting `develop` or `main`, not on feature-branch pushes alone
4. **Check CI status** - ensure all checks pass before merging

### **For Merges:**
1. **Create PR** - target `develop` for normal work
2. **Ensure CI checks pass** - the eight `Application Quality` jobs run for every pull request
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

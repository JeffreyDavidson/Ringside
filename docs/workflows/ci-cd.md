# GitHub Actions & CI/CD

## Current Workflow Configuration

The project uses four automated workflows:

### 1. **CI Pipeline** (`.github/workflows/ci.yml`)
**Trigger**: Pushes and pull requests targeting `develop` or `main`
**Purpose**: Comprehensive testing and static analysis

**What it does:**
- Runs on PHP 8.5 with Laravel 13.* on Ubuntu 24.04
- Executes all test suites in parallel (Feature, Integration, Unit)
- Runs PHPStan static analysis (`composer test:types`)
- Uses optimized `.env.testing` configuration
- Caches dependencies and PHPStan results for performance

**Key Features:**
- **Parallel Test Execution**: `--parallel` flag for faster test runs
- **Memory Optimization**: 512M memory limit for PHP
- **Dependency Caching**: Composer and PHP extension caching
- **Problem Matchers**: Enhanced error reporting in GitHub UI

### 2. **Security Scan** (`.github/workflows/security-scan.yml`)
**Trigger**: Pushes and pull requests
**Purpose**: Dependency and security checks

### 3. **TIA Baseline** (`.github/workflows/tia-baseline.yml`)
**Trigger**: Changes to the test-impact baseline configuration
**Purpose**: Validate the Pest Test Impact Analysis baseline

### 4. **Coverage Testing** (`.github/workflows/coverage.yml`)
**Trigger**: Manual dispatch
**Purpose**: Generate PCOV coverage reports when a coverage run is requested

## Branch Protection Integration

**GitHub Branch Protection Rules Applied:**
- `develop` and `main` branches require PRs
- CI workflow must pass before merge (`ci` status check)
- Required CI checks must pass before merge
- Direct pushes to protected branches are blocked

**Workflow Behavior with Branch Protection:**
```bash
# ✅ Conventional branch - CI runs on push
git push origin chore/update-documentation

# ✅ PR to develop - required CI checks run
gh pr create --base develop

# ❌ Direct push to develop - blocked by GitHub
git push origin develop  # Will fail
```

## Troubleshooting Common Issues

### **CI Workflow Failures**

**Test Failures:**
```bash
# Check specific test suite locally
./vendor/bin/pest --testsuite=Feature --stop-on-failure
./vendor/bin/pest --testsuite=Integration --stop-on-failure
./vendor/bin/pest --testsuite=Unit --stop-on-failure

# Run with same environment as CI
cp .env.testing .env
php artisan config:cache
./vendor/bin/pest --parallel
```

**PHPStan Errors:**
```bash
# Run PHPStan locally with same settings
composer test:types

# Clear PHPStan cache if needed
./vendor/bin/phpstan clear-result-cache
```

**Memory Issues:**
- CI uses 512M memory limit
- Local development may need: `php -d memory_limit=512M vendor/bin/pest`

### **Code Styling Issues**

Run the repository's Composer lint script locally:
```bash
composer lint
composer test:lint
```

### **Coverage Workflow Issues**

**Coverage Not Generating:**
```bash
# Run coverage locally
composer test:unit

# Generate a local coverage report with the project minimum
./vendor/bin/pest --coverage --min=44 --coverage-clover=coverage.xml
```

**Codecov Upload Failures:**
- Check `CODECOV_TOKEN` secret is set in GitHub repository
- Verify coverage.xml file is generated successfully

## Environment Configuration

### **Optimized Test Environment** (`.env.testing`)
```env
# Performance optimizations for CI/CD
DB_DATABASE=:memory:          # SQLite in-memory database
CACHE_STORE=array            # Array-based cache (fastest)
SESSION_DRIVER=array         # Array-based sessions
QUEUE_CONNECTION=sync        # Synchronous queue processing
MAIL_MAILER=array           # Array mail driver (no emails sent)

# Generated application key for consistent testing
APP_KEY=base64:yBIJTxbDrdZCu2t7A7fAfdThy+LL6GEOArWwLJIfncQ=
```

**Why These Settings:**
- **Memory DB**: Fastest database operations for tests
- **Array Drivers**: Eliminate I/O operations for cache/sessions
- **Sync Queue**: Immediate job processing in tests
- **Consistent Key**: Same key across all CI runs

## Workflow Best Practices

### **For Feature Development:**
1. **Create a conventional branch** - for example `feat/new-feature` or `chore/update-documentation`
2. **Push early and often** - get CI feedback quickly  
3. **Run the local checks** - use `composer lint` and the affected test commands
4. **Check CI status** - ensure all required checks pass before PR

### **For Protected Branch Merges:**
1. **Create PR** - target `develop` for normal work
2. **Ensure required CI checks pass** - required for merge
3. **Run coverage when needed** - dispatch `coverage.yml` for a PCOV report
4. **Merge when green** - all status checks must pass

### **Local Development Tips:**
```bash
# Use same test environment as CI
cp .env.testing .env && php artisan key:generate

# Run tests like CI does
composer test:push

# Check types like CI does  
composer test:types

# Verify styling before push
composer test:lint
```

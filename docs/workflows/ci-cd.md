# GitHub Actions & CI/CD

## Current Workflow Configuration

The project uses a streamlined CI/CD approach with 3 automated workflows:

### 1. **CI Pipeline** (`.github/workflows/ci.yml`)
**Trigger**: All branches except `master`  
**Purpose**: Comprehensive testing and static analysis

**What it does:**
- Runs on PHP 8.4 with Laravel 13.* on Ubuntu 24.04
- Executes all test suites in parallel (Feature, Integration, Unit)
- Runs PHPStan static analysis (`composer test:types`)
- Uses optimized `.env.testing` configuration
- Caches dependencies and PHPStan results for performance

**Key Features:**
- **Parallel Test Execution**: `--parallel` flag for faster test runs
- **Memory Optimization**: 512M memory limit for PHP
- **Dependency Caching**: Composer and PHP extension caching
- **Problem Matchers**: Enhanced error reporting in GitHub UI

### 2. **Code Styling** (`.github/workflows/pint.yml`)
**Trigger**: Feature branches only (excludes `master` and `development`)  
**Purpose**: Automatic Laravel Pint code formatting

**What it does:**
- Runs Laravel Pint 1.18.3 on PHP file changes
- Automatically commits styling fixes with "Fix styling" message
- **Branch Protection**: Excluded from protected branches to prevent direct commits
- Uses `contents: write` permission for auto-commits

**Important**: This workflow will NOT run on `development` or `master` branches due to branch protection rules.

### 3. **Coverage Testing** (`.github/workflows/run-tests-pcov-pull.yml`)
**Trigger**: Pushes and PRs to `development` and `master` branches  
**Purpose**: Test coverage reporting for protected branches

**What it does:**
- Runs comprehensive test suite with PCOV coverage
- Generates coverage reports (clover format)
- Uploads coverage data to Codecov
- Enforces strict coverage requirements
- **Critical for Protected Branches**: Required status check for PR merges

## Branch Protection Integration

**GitHub Branch Protection Rules Applied:**
- `development` and `master` branches require PRs
- CI workflow must pass before merge (`ci` status check)
- Coverage workflow must pass for protected branches
- Signed commits required
- Direct pushes blocked for all users

**Workflow Behavior with Branch Protection:**
```bash
# ✅ Feature branch - all workflows run
git push origin feature/new-feature

# ✅ PR to development - coverage workflow runs
gh pr create --base development

# ❌ Direct push to development - blocked by GitHub
git push origin development  # Will fail
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

**Styling Workflow Not Running:**
- Verify you're on a feature branch (not `development`/`master`)
- Check that PHP files were modified
- Ensure branch is pushed to GitHub

**Manual Styling Fixes:**
```bash
# Run Pint locally
./vendor/bin/pint

# Check what Pint would change
./vendor/bin/pint --test
```

### **Coverage Workflow Issues**

**Coverage Not Generating:**
```bash
# Run coverage locally
./vendor/bin/pest --coverage --min=80

# Check coverage with same settings as CI
./vendor/bin/pest --parallel --coverage-clover=coverage.xml
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
1. **Create feature branch** - styling workflow will run automatically
2. **Push early and often** - get CI feedback quickly  
3. **Let Pint auto-fix** - don't manually fix styling issues
4. **Check CI status** - ensure all checks pass before PR

### **For Protected Branch Merges:**
1. **Create PR** - triggers coverage workflow
2. **Ensure CI passes** - required for merge
3. **Review coverage** - check Codecov reports
4. **Merge when green** - all status checks must pass

### **Local Development Tips:**
```bash
# Use same test environment as CI
cp .env.testing .env && php artisan key:generate

# Run tests like CI does
./vendor/bin/pest --parallel --testsuite=Feature,Integration,Unit

# Check types like CI does  
composer test:types

# Verify styling before push
./vendor/bin/pint --test
```
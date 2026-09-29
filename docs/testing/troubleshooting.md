# Troubleshooting

## Common Test Issues
- **Memory Issues**: `phpunit.xml` sets `memory_limit=4G` for every (parallel) test process; raise it there if a suite runs out of memory
- **Parallel Issues**: Some tests may not be parallel-safe
- **Database Issues**: Ensure proper database cleanup
- **Timing Issues**: Use `testTime()->freeze()` for time-sensitive tests

## Running Coverage Locally (Herd)
`composer test:coverage` requires a coverage driver (PCOV or Xdebug). Without one it fails immediately with "No code coverage driver is available." Herd's PHP ships without PCOV, so load it for one shell session through a scratch ini directory. Keep Herd's own ini directory first in `PHP_INI_SCAN_DIR` so its settings still apply:

```bash
mkdir -p /tmp/pcov-ini
cat > /tmp/pcov-ini/pcov.ini <<'INI'
extension=/opt/homebrew/lib/php/pecl/20250925/pcov.so
pcov.enabled=1
pcov.directory=/path/to/ringside/app
INI

export PHP_INI_SCAN_DIR="$HOME/Library/Application Support/Herd/config/php/85:/tmp/pcov-ini"
composer test:coverage
```

Adjust the `pecl/<api>` directory to your PHP API version (`ls /opt/homebrew/lib/php/pecl/*/pcov.so`) and the Herd path to your PHP version. The gate requires 100% and runs non-parallel (about 2 minutes). Do not add `--parallel`: it loses `match` header lines when merging worker coverage and produces false gaps.

## Debug Techniques
```bash
# Run single test with debug info
./vendor/bin/pest tests/Unit/Models/WrestlerTest.php --stop-on-failure

# Show test coverage (needs a coverage driver, see below)
composer test:coverage

# Profile test performance
./vendor/bin/pest --profile
```

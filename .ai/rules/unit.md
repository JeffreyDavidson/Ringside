---
paths:
  - 'tests/Unit/**'
---

# Unit

## Refresh databases in application test suites
Use RefreshDatabase for Unit tests; keep database reset configuration centralized in tests/Pest.php.

## Keep unit tests database-free
Unit tests may extend the Laravel TestCase when they need framework services, but they must not use RefreshDatabase or persist models. Place database-backed behavior in the mirrored Integration suite.

## Keep unit tests framework-free
Unit tests use Pest's default PHPUnit test case and must not boot Laravel, use RefreshDatabase, or persist models. Move tests that need application services, facades, the container, or database state to the mirrored Integration or Feature suite.

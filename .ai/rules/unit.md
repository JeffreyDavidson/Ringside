---
paths:
  - 'tests/Unit/**'
---

# Unit

## Keep unit tests framework-free
Unit tests use Pest's default PHPUnit test case and must not boot Laravel, use RefreshDatabase, resolve application services, or persist models. Move framework- or database-backed behavior to the mirrored Integration or Feature suite.

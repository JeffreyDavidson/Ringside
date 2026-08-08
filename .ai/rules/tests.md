---
paths:
  - 'tests/**'
---

# Tests

## Use Pest tests
Write tests with Pest's functional test, describe, and expectation APIs rather than PHPUnit test classes.

## Use TestDouble for application collaborators
Use JMac\Testing\Double::for() for doubles of application collaborators. Bind container-resolved doubles through the application container and explicitly verify their expectations.

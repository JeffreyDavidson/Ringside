---
paths:
  - 'tests/**'
---

# Tests

## Use Pest tests
Write tests with Pest's functional test, describe, and expectation APIs rather than PHPUnit test classes.

## Use TestDouble for application collaborators
Use JMac\Testing\Double::for() for doubles of application collaborators. Bind container-resolved doubles through the application container and explicitly verify their expectations.

## Organize tests by behavior and AAA phases
Keep each test file focused on one subject, group related behavior with describe blocks, and make Arrange, Act, and Assert phases explicit. Keep each Act call on its own line.

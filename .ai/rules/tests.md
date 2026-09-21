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

## Keep test suites aligned to application boundaries
Feature tests cover HTTP endpoints and authorization; Integration tests cover database, framework, component, and multi-action domain behavior; Unit tests remain framework- and database-free; Browser tests cover real user journeys in Pest Browser. Place each test beside the application boundary it exercises.

## Assert observable behavior
Test rendered output, returned data, persisted state, dispatched events, authorization, and validation outcomes. Do not inspect source strings, imports, comments, method counts, or private implementation structure with reflection; reserve reflection for architecture constraints that cannot be expressed through Pest architecture expectations.

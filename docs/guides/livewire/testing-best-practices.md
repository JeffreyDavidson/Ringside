# Livewire Testing Best Practices

## Overview

Conventions that apply to every Livewire test. The suite-wide rules are in `.ai/rules/tests.md` and
[Testing Guidelines](../../development/testing-guidelines.md); this page lists the ones that matter most when
writing component tests.

## Style

- Write Pest tests (`it()`, `test()`, `describe()`); never PHPUnit classes.
- Keep Arrange, Act, and Assert phases explicit and put each Act call on its own line.
- Prefer global helpers over `$this`: `use function Pest\Laravel\actingAs;` and
  `use function Pest\Livewire\livewire;`.
- Group related behavior with `describe()` and turn repeated cases into datasets.
- Use named routes and factories (with their states) for setup.

## Assert observable behavior

Assert what the component renders, returns, dispatches, persists, and authorizes:

```php
$component
    ->assertDispatched('wrestler-updated')
    ->assertDispatched('flash-message', type: 'status', message: $message)
    ->assertSee('Edit Rey Mysterio')
    ->assertHasErrors(['form.name' => 'required']);
```

Do not inspect source strings, method counts, or protected internals with reflection. Do not assert that a
component "has" a method the framework or base class supplies.

## Keep tests deterministic

- The clock is frozen for Integration and Feature tests in `tests/Pest.php`. Use `travel()` to move it, never
  `sleep()`, and do not compute expected dates outside the frozen clock (including in dataset definitions).
- Do not let random Faker output choose a branch or a search term. Use fixed, distinctive values, and force
  boolean branches with `forceFakerBoolean()` from `tests/Helpers/FakerHelpers.php`.

## Doubles

Use `JMac\Testing\Double::for()` for application collaborators such as Actions. Bind container-resolved doubles
with `app()->instance()` and call `verify()` on them. Use factories rather than doubles for Eloquent models.

## Authorization and failure paths

Every protected component needs a forbidden case (`assertForbidden()` for a guest and a `basicUser()`) as well as
the administrator happy path. Domain failures (`BaseBusinessException`) are translated at the Livewire boundary
and should be asserted as user-facing feedback; programmer errors are not caught.

## Coverage

`composer test:coverage` runs non-parallel and requires 100% line coverage of `app/`, including `app/Livewire`.
Do not add `@codeCoverageIgnore` or tests that only execute a line; delete unreachable code instead.

## Related Documentation

- [Testing Guide](testing-guide.md)
- [Testing Guidelines](../../development/testing-guidelines.md)

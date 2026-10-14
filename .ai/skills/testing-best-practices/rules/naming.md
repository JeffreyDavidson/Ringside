# Naming and Structure

## File Layout

- Follow the repository's suite layout and naming. A useful default is `{ClassName}Test.php` at the same relative path as the class under test.
- Choose Unit for pure logic and the configured framework-backed suite for code needing Laravel or a database; an Action is not automatically a unit test.
- Follow the project's convention for fixture files. If none exists, put fixtures in `tests/Fixtures/` and load them by path.
- Move large literal values out of the test body and into fixture files.

## Test Function

Respect the existing Pest or PHPUnit style. The following declaration and grouping guidance applies to Pest; use equivalent test methods and data providers in PHPUnit. If no neighboring Pest files exist:

- Use `it()` for the behavior of the code, and write the name as a verb phrase.
- Use `test()` for a declarative fact, such as a grant in a policy, the labels of an enum, or the shape of a serialized model.

Use one Pest declaration style in each file. Use either `it()` or `test()` consistently.

## Naming Tests

The name of a test is a specification. State the user-visible result and the condition that causes it.

- Name the behavior, and not the method under test. The file name already gives the class.
- Give the exact status code in the name of a test for an API error.
- Do not write `Given`, `When`, or `Then` in the name.

```php
it('returns 401 when no token is provided', function () { ... });
it('does not include deployments from deleted environments', function () { ... });
it('falls back to the default region when none is configured', function () { ... });
```

A name that joins independent operations (for example saves, reopens and restores) is a prompt to review whether those operations need separate tests. A long name alone does not prove duplication.

Use a verb that describes a result, such as `returns`, `renders`, `creates`, `dispatches`, `rejects`, `forbids`, `falls back`, or `does not`.

Do not write `it('works correctly')` or `it('returns data')`, because neither specifies a meaningful result. Do not write `it('handleMethod creates record')`, because it names a method rather than behavior.

## Grouping

Use `describe()` if one file covers separate actions in a lifecycle. An example is a controller with the actions `index`, `show`, `store`, `update`, and `destroy`.

Prefer flat tests for a small single-action file when a wrapper only repeats its filename. Keep a group when it distinguishes meaningful behavior or setup and improves navigation.

Do not add a group merely for different input values that belong to a homogeneous dataset, or when the extra level does not improve readability.

## Separate Scenarios

Split a test when conditional setup or assertions select different scenarios: guests versus signed-in actors, allowed versus refused roles, valid versus invalid input, or different expected side effects/statuses. Do not hide the same branching behind `match`, a ternary, a callable assertion dataset or a generic expected-status parameter.

Named datasets belong to one contract with the same setup/action/assertion structure. Owners and managers allowed to edit can share a dataset; guest login redirects, member refusals and foreign-record 404s get separate tests. See `test-data.md` for fixture and dataset choices and `assertions.md` for necessary sequences.

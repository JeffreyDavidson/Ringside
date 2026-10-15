# Exception Writing Guide

Business exceptions describe why an operation was refused. Actions and eligibility classes decide when a rule fails; the exception only carries the failure. The enforced rules live in `.ai/rules/exceptions.md` and `tests/Feature/Architecture/ExceptionArchitectureTest.php`; this page shows how to apply them.

## Where it goes

The folder is the domain of the record the rule protects, and it matches a domain folder under `app/Models` or `app/Actions`, for example `app/Exceptions/Titles/` or `app/Exceptions/Roster/Stables/`.

Three shared folders exist:

- `Roster/Individuals` for rules shared by wrestlers, managers and referees.
- `Scheduling` for booking and availability conflicts across domains.
- `Lifecycle` for lifecycle rules that belong to no single record.

Do not create type folders such as `BusinessRules/` or `Data/`. A future shared "changed by someone else" exception goes at the top level next to `BaseBusinessException`.

## How to name it

Use one of two families:

- `CannotBe{Verb}edException` for a disallowed state change (`CannotBeRetiredException`), with one static factory per reason.
- `{Feature}Exception` for a feature error (`BudgetException`).

Classes are `final`, extend `BaseBusinessException` and end in `Exception`.

## What it looks like

```php
final class CannotBeRetiredException extends BaseBusinessException
{
    public static function alreadyRetired(Title $title): static
    {
        $context = self::formatModelContext($title);

        return new self(__('titles.errors.retired.already_retired', ['context' => $context]));
    }
}
```

- Add a factory only alongside the code that throws it.
- Callers use the factory, never `new` on the exception.
- When the UI must tell the failure apart, create it with `self::forReason()` and a `BusinessRuleReason` case. Never parse `getMessage()`.

## Messages

Messages are shown to users, so they are translated. Use the key `<domain>.errors.<operation>.<case>` (for example `titles.errors.retired.already_retired`) and say what went wrong and, where useful, what to do next.

## Documentation

Keep docblocks for what PHP types cannot say. Put business context and workflows in `docs/architecture`, not in the exception.

## Tests

Test the behaviour that throws the exception through the action or eligibility class, and assert the translated message where the wording matters. `tests/Integration/Exceptions` holds the message checks.

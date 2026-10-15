---
paths:
  - 'app/Exceptions/**'
---

# Exceptions

## Use typed business failure reasons
When presentation must distinguish a failure, create the exception with self::forReason() and a stable BusinessRuleReason case; never derive application behavior by parsing getMessage(). General failures retain BusinessRuleReason::General.

## Write translated, user-facing messages
Exception messages are shown to users. Build them with translation keys named `<domain>.errors.<operation>.<case>`, never hard-coded English. Presentation that must branch still uses the typed reason, not the message text.

## Organize exceptions by the domain they protect
The folder is the domain of the record the rule protects, and it matches a domain folder of the app (a directory under `app/Models` or `app/Actions` with the same relative path). The only shared folders are `Roster/Individuals` (rules shared by wrestlers, managers and referees), `Scheduling` and `Lifecycle`. Do not create type folders such as `BusinessRules` or `Data`. A shared "changed by someone else" exception goes at the top level next to `BaseBusinessException`. `ExceptionArchitectureTest` enforces the folders.

## Use two naming families
Use `CannotBe{Verb}edException` for disallowed state changes, with one static factory per reason. Use `{Feature}Exception` for feature errors.

## Keep exceptions as failure descriptions
Actions, validation concerns, and domain policies decide when a rule fails. Exception classes describe the failure and must not perform validation, query application state, or orchestrate behavior.

## Create exception factories from enforced rules
Add an exception factory only alongside an enforced caller. Do not pre-populate exception classes with hypothetical scenarios or future business rules.

## Keep exception documentation focused
Put workflow and business-context explanations in docs/architecture. Exception docblocks document only information PHP types cannot express; do not add usage examples or business essays.

## Use concrete final business exceptions
Concrete domain exceptions extend BaseBusinessException, are final, and use an Exception suffix.

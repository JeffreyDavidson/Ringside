---
paths:
  - 'app/Rules/**'
---

# Rules

## Separate shared and model-specific Stable eligibility
Put employment, suspension, current-Stable exclusivity, and membership-date checks in App\Rules\Stables\CanJoinStable, parameterized by the eligible member model class. Keep constraints that apply only to one member type, such as wrestler injuries or duplicate representation through a selected tag team, in focused model-specific validation rules.

## Fail invalid records through validation
Custom validation rules must report missing or malformed selected records through the validation failure callback. Do not use findOrFail(), firstOrFail(), or sole() for user-supplied identifiers inside a rule; an exists rule may precede the custom rule, but the rule must remain safe when invoked independently.

## Write rules as ValidationRule classes with translated messages
A custom rule implements `ValidationRule`, lives in its domain folder under `app/Rules` (`Wrestlers`, `TagTeams`, `Shared`), and is named for the condition it checks (`IsNotInjured`, `CanJoinStable`). Fail with a translation key (`wrestlers.validation.*`), reject a value that is not an int or string before querying, and leave record and state lookups to Eloquent or the lifecycle classes. Rules that depend on the booked event take it through the constructor (`IsBookable`).

## Test each rule beside its domain
Test a rule at its mirrored path in `tests/Integration/Rules`: pass the value through a real validator with a valid and an invalid record, and assert the translated failure message. A rule that takes an event or match needs a case for each of its inputs.

## Check input shape in a rule, not in a Lifecycle guard
A check that only needs the submitted input (a field required when another has a value, a link that must carry no credentials) belongs in the validator: a built-in rule such as `required_if`, or a Rule object in its domain folder (`HttpsUrlWithoutCredentials`), so the error lands on the field that is wrong. Lifecycle guards keep the checks that need records or state (a usable contact, a call time that exists, a limit counted across items). A save that must be safe from any caller runs the validation again inside the Action, as `SaveEventProductionAction` does with `EventProductionRequirements::validatedInput()`.

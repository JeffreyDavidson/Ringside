# FormRequest Contracts

Use for FormRequest changes and affected HTTP consumers. Read [endpoint tests](endpoint-tests.md), [assertions](assertions.md) and the target project's rules. Confirm the installed Laravel/test versions before using APIs; consult version-matched [validation](https://laravel.com/framework/docs/validation) and [HTTP testing](https://laravel.com/framework/docs/http-tests) documentation or installed source. Examples illustrate Pest syntax, not a mandate to migrate PHPUnit suites.

## Trace the Actual Boundary

Read the request, every consuming route/handler in the affected flow, relevant middleware, authorization, payload accessors and downstream writes/effects. Include unchanged consumers when a shared request changes. Trace the actual preparation/authorization/validation order for the installed version. Global trimming and empty-string conversion, request preparation, explicit failure handlers and model casts can produce different observable values. Do not infer pre-validation or authentication behavior from a model cast alone.

Characterize existing behavior before a behavior-preserving refactor. A cleaner normalization, boolean conversion or default can still change the contract; flag a surprising product/security behavior separately rather than silently preserving it as desirable or changing it without authorization. Do not require new casts, traits, Actions, packages or request abstractions for a small concern. An input-free request may intentionally have no rules; inspect its actual purpose instead of inventing validation or authorization methods.

## Preparation and Validated Payloads

Select cases from changed decisions and real input consumers, not a universal matrix for every field:

- Normalized strings and applicable missing, null, empty, array and numeric inputs. A string operation must not turn malformed input into a server error. Keep distinct cases when the lifecycle treats them differently.
- Presence versus value: omitted keys, null, false, zero, `'0'` and checkbox values can differ. `filled()` and boolean conversion are not interchangeable. Test the application's intended semantics and method-specific variants, including different rules for different verbs.
- Validated data actually used by the handler: ignored or rejected untrusted fields, nested key allowlists where configured, collection defaults, indexing and identity/scalar coercions. Assert what reaches persistence or the observable collaborator, rather than only the return value of `rules()` or a payload method.
- IDs and uniqueness at the intended ownership scope, excluded/deleted records where relevant, optional files and metadata, and otherwise-valid refusals with no partial writes or success effects.

Prefer actual named-route requests with FormRequests and internal workflows intact. Use lower-layer rule tests for pure reusable value matrices and retain each distinct HTTP wiring/preparation/failure contract. Focused request-level tests may supplement HTTP coverage for otherwise unreachable behavior with a stated reason; do not duplicate every framework rule or force a separate test file per request. Follow explicit project suite/mirroring requirements.

## Refusals and Old Input

For browser form failures that redirect back with errors, assert the intended redirect, specific relevant errors and preserved nonsensitive old input with `assertSessionHasInput()`. Assert relevant unchanged state and prevented effects. Check password, password-confirmation and other sensitive-field exclusions according to the actual project's contract; do not teach that all submitted data should be flashed. API validation usually has a JSON error contract instead of flashed form input; do not invent sessions or redirects for it.

Determine expected old input from the real failure path. Automatic validation failure and an explicit `withInput()` branch may flash different prepared values. Do not assume a lowercased stored value also describes the redisplayed input. Keep expected values independent of the request's transformation method.

### Nullable Old Input

In Laravel versions with the closure overload, use strict comparisons when the value/type matters:

```php
// Assert
$response
    ->assertRedirect(route('profile.edit'))
    ->assertSessionHasErrors('email')
    ->assertSessionHasInput('email', fn (mixed $input): bool => $input === null)
    ->assertSessionHas('_old_input', fn (array $input): bool => array_key_exists('email', $input))
    ->assertSessionMissing('_old_input.password')
    ->assertSessionMissing('_old_input.password_confirmation');
```

This example assumes explicitly preserved null is the contract. In the installed Laravel implementation, passing literal null as the second `assertSessionHasInput()` argument invokes the presence check, not null equality. A closure receives null for an absent key too; the separate key-existence check distinguishes an explicitly flashed null from missing input. For a nested key use the installed framework's key-existence API appropriate to that structure. Check that a closure exists and reads the expected representation in the target version; otherwise use equivalent native session assertions. Do not replace a null-preservation contract with a truthiness assertion or `hasOldInput()`.

## Coverage Map and Completion

Before expensive full verification, inspect the changed contracts and all new/materially rewritten refusal cases, including expanded dataset rows, for missing old-input or sensitive-input assertions. This early review complements focused tests and every required full gate; it does not authorize skipping them or claiming CI is green.

Retain a compact inventory for an audit or review:

| Request | Consuming named route, verb and handler | Preparation/validation/payload or refusal contract | Test file, named case and dataset rows | Gap, justified not applicable or verification limit |
| --- | --- | --- | --- | --- |

Inspect the cases and actual wiring; filenames, rule arrays, coverage percentages and passing tests alone do not establish the map. Record meaningful allowed/refused paths, changed input combinations and any missing access. Preserve existing meaningful scenarios and assertions during authorized consolidation. Use [portable JSON assertions](assertions.md#database-portable-json-assertions) for stored payloads, retaining meaningful list order and scalar types on every configured database engine.

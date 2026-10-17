# Middleware Contracts

These are Jeffrey's shared application-middleware conventions, subject to explicit project guidance. Confirm the installed Laravel/PHP versions and existing registration/testing setup before choosing APIs. Updating guidance or reviewing code does not authorize application renames, moves, new dependencies or CI changes.

## 1. Name the Purpose

Name middleware by its action or purpose, without a `Middleware` suffix. Guard names describe the condition they ensure, such as `EnsureTeamIsSelected` or `EnsureSubscriptionIsActive`. Other responsibilities use suitable verbs, such as `SetLocale`, `AddSecurityHeaders` or `RedirectAuthenticatedUsers`. Do not force `Ensure` onto every middleware or accept a vague `CheckAccess` name without reviewing its actual responsibility. This is a project naming convention, not a Laravel runtime restriction. Laravel demonstrates `EnsureTokenIsValid` in its [middleware documentation](https://laravel.com/framework/docs/13.x/middleware#defining-middleware); use version-matched documentation for implementation.

## 2. Own One Coherent Request/Response Concern

Middleware inspects or guards the request, establishes request context, or modifies the response. Keep one coherent responsibility; guards may consult existing policies or collaborators, but model-specific permission rules remain in policies/gates and business workflows in appropriate application layers. Flag middleware that also creates orders, charges customers or performs unrelated state transitions. Appropriate logging, session access or context lookups are not inherently misplaced IO; review purpose rather than requiring pure functions, tiny classes or wrappers for every concern.

## 3. Reuse Framework Capabilities

Use existing authentication, authorization, signature, throttling and verification middleware when they satisfy the requirement. A custom replacement needs a concrete application requirement and evidence that the existing capability does not cover it; legacy repetition alone is not justification. Test application configuration and custom behavior rather than reproducing vendor/framework test suites.

## 4. Review Registration and Order

Trace aliases or class assignments, global/group/route registration, exclusions and parameters. Apply route middleware in route files, not controllers; register aliases, groups and global stacks through the installed Laravel version's supported configuration and project layout. In modern applications this can be `bootstrap/app.php`; do not impose that path on an older established layout. Review dependencies on authentication, session state or model binding, and test meaningful order-sensitive behavior. Do not invent priority overrides or reorder the whole stack without a concrete need. See the version-matched [registration and ordering guidance](https://laravel.com/framework/docs/13.x/middleware#registering-middleware).

## 5. Require Matching Feature Coverage and Real Wiring

Every concrete application-owned middleware has a matching Feature test at its complete mirrored path: `app/Http/Middleware/EnsureTeamIsSelected.php` maps to `tests/Feature/Http/Middleware/EnsureTeamIsSelectedTest.php`; nested namespaces mirror too. Genuine abstract bases and framework/vendor classes are exempt from this application-file ownership requirement. Shared endpoint tests may supplement but do not replace the matching file.

The matching test exercises meaningful application middleware behavior and actual registered route wiring with the middleware active and valid surrounding prerequisites. A filename, architecture assertion or test-only route alone does not prove application registration. A focused direct `handle()`/continuation test or isolated test route may supplement coverage when it exposes a distinct boundary, such as downstream execution; do not require duplicate unit and HTTP tests for every case. Keep real application permissions/workflows intact and fake external boundaries as appropriate under [isolation](isolation.md). Standard simulated HTTP tests and framework defaults are not proof of live browser/CSRF behavior; use the installed framework guidance for any such claim.

## 6. Assert the Middleware's Actual Behavior

- Guards cover permitted and refused paths. Assert the actual downstream response/effects for permission, and demonstrate that refusal prevents downstream work and relevant state/effects remain unchanged; a refusal status alone is insufficient.
- Context middleware asserts the context observed by downstream behavior, such as the selected tenant or locale. Cover meaningful absence/invalid-context cases when supported instead of inventing a denial for every context setter.
- Response middleware asserts its intended changes and preservation of relevant status, body, headers and cookies. Test the contract rather than incidental header ordering or every framework detail. Cover applicable before/after or terminable behavior through the installed lifecycle; do not invent a terminable handler or claim it executed without evidence.

## 7. Fail for the Intended Reason

Arrange otherwise valid authentication, route binding, target scope and input so an earlier middleware or validation rejection cannot masquerade as the middleware under test. Deliberately vary the named condition and assert the specific browser redirect or API response, plus relevant unchanged state/effects. Use a valid allowed control or focused continuation evidence when needed to establish the intended boundary. Follow [endpoint failure and authorization guidance](endpoint-tests.md#fail-for-the-intended-reason), including distinct role/route paths; lower policy tests do not replace HTTP wiring coverage. Do not disable or mock the target middleware to claim its behavior is tested.

## 8. Inspect the Applicable Stack and Retain Evidence

For affected middleware or registration changes, Reviewer inventories application middleware, related registrations and named cases across the applicable stack, including relevant unchanged files and prerequisites. Cover distinct parameter variants, exclusions, request contexts and order-dependent behavior when they change the contract. Representative wiring tests may cover equivalent routes in a group only after verifying that membership/configuration is the same; map differences separately and retain stricter project coverage. An excluded route needs meaningful coverage of the exclusion, not a blanket bypass of middleware throughout the suite.

| Middleware and responsibility | Alias/class and registration scope | Parameters, exclusions and order dependencies | Mirrored Feature file and named cases | Gap or not applicable |
| --- | --- | --- | --- | --- |

Keep explicit Arrange/Act/Assert, `$response` for HTTP results, named routes, readable setup and related `->and()` assertions under [assertions](assertions.md) and [review](review.md). Keep controlled sequences only when the named order/lifecycle defect requires them. Report exact rule/code references, applicable conflicts, inspected paths and executed checks separately; missing access is a limitation, not complete review. Separate unrelated legacy findings from the changed or relied-upon flow. Architecture checks prove naming/path/registration declarations only; do not claim CI enforcement or runtime behavior until the appropriate checks actually ran.

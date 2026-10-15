# Endpoint Tests

## How to Write the Test

Fetch `https://laravel.com/framework/docs/http-tests` for the request helpers, the authentication helpers, and the response assertions. Confirm the name before you use it, and do not guess an assertion.

Choose an assertion based on the subject of the check: the status, a header, a redirect, the JSON body, the session, a validation error, or the view. Laravel provides a named assertion for each subject that identifies the incorrect value.

## Exercise the Application HTTP Path

Controller Feature tests request the actual registered named routes through the framework HTTP helpers. Keep relevant route middleware, model binding, FormRequests, policies/gates and internal application Actions active. Do not invoke a controller method directly, disable authentication/authorization middleware, replace a FormRequest, or mock the application's own workflow merely to make the endpoint pass. Lower-layer tests may test their own boundary, but do not substitute for HTTP wiring coverage.

Fake nondeterministic/external boundaries such as outbound HTTP, mail, queues, storage and time as appropriate; assert meaningful interactions and non-interactions. Read [isolation](isolation.md) before choosing doubles. Preserve the framework's documented test defaults and report any deliberate project exception: standard HTTP tests are simulated requests, not proof of live browser/CSRF behavior. Any explicit middleware bypass needs a scoped reason and separate intact coverage for the behavior it bypasses; it cannot count as authorization coverage.

## Fail for the Intended Reason

Build validation cases with an authorized actor, an existing correctly scoped target where needed and otherwise valid input. Change the input relevant to the named validation contract and assert the specific failing field/message with the installed framework's native assertions. Multiple invalid fields are appropriate when required-field behavior is the named contract; unrelated invalid fields must not mask the case under test.

Build authorization cases with an existing target and valid input so a missing record, failed binding or validation does not stand in for the intended denial. For explicit binding/not-found cases, arrange the missing or foreign target deliberately and name that boundary. Distinguish guest redirects, role/policy denials, tenant/scope refusals and validation errors using the actual route, actor, target and response contract. A generic redirect, 403 or 404 alone does not identify its cause. Trace the gate/middleware/validation flow and use a valid authorized control when needed to establish the intended boundary; never infer which mechanism ran from the status alone. Failed writes also assert relevant state and side effects remain unchanged.

## Endpoint Coverage

Apply the [per-handler coverage map](controllers.md#coverage-for-every-routed-handler), not just one success test per controller file. For each registered handler and distinct HTTP contract, write tests for each applicable case:

- The request has missing or invalid authentication.
- The request comes from a different tenant, team, or organization.
- The user has an insufficient role or permission.
- The request does not satisfy a route or scope constraint.
- The request fails the validation.
- The request is valid. Assert the relevant response/view/payload; for writes, assert persisted state and side effects, and for reads assert the returned data contract.

Assert the application's actual behavior rather than a generic status code. An API returns `401` for a missing or invalid token, while a browser endpoint redirects to the sign-in route.

## Read Endpoint Contracts

For list/detail handlers, assert the actual records and meaningful fields returned through view data or JSON. Include contrast records to prove exclusion where tenant, ownership, visibility or filter rules apply. Cover supported filtering, ordering, pagination and empty states when they are part of the endpoint's contract; do not invent these features for every read route. Assert the returned record identities/order and relevant page boundaries or totals rather than only `200 OK`, a view name or a broad substring. Use deterministic fixture values and tie-breaking expectations only when ordering is specified. Do not pin incidental HTML or serialization fields that are not part of the contract.

## Tenant Isolation

Assert the status code returned for a cross-tenant request. Use `404` rather than `403` when one tenant must not learn that another tenant's record exists, because `403` confirms its existence.

## Test Authorization at the Policy Level

An HTTP test shows that the endpoint performs authorization. It cannot identify which mechanism refused the request because middleware, a policy, and a call to `abort()` can all return `403`.

- Assert the complete matrix of the permissions against the policy or the gate. A failure then names the rule that is not correct.
- Test authorization wiring for every registered handler and each distinct HTTP authorization path: the ability and resource arguments, route/middleware context, role-dependent branch and relevant scope. One refused-role test does not clear other handlers or different authorization paths. Use otherwise valid requests, the actual expected refusal and unchanged-state/effect assertions for writes; cover the applicable permitted path too.
- Keep the complete pure permission matrix in policy/gate tests, while retaining the HTTP cases that protect distinct wiring and role/route behavior. Representative roles may share a case only when inspection establishes the same HTTP path and contract; a role-dependent branch needs its own case. Homogeneous roles can use a named dataset. Follow stricter project matrices and preserve established coverage; do not remove HTTP cases just because policy tests pass.
- Use the helper of the project that asserts the ability and the arguments of the gate, if such a helper exists.

## Browser Tests

The examples below assume the Pest browser plugin. Use the target project's installed browser tooling and version-matched documentation; do not introduce a plugin or migrate Dusk/other suites just to match these examples.

Write a browser test only for JavaScript behavior that an HTTP test cannot reach, such as modal interaction, drag-and-drop, live search, or client-side validation. Browser tests are slower than HTTP tests and can fail for reasons unrelated to the code under test.

- Assert the state that the user can see, and assert the state in the database that the interaction saves.
- Wait until the test reaches the required state. Do not wait for a fixed number of seconds, which can fail on a slower machine.
- Call `assertNoJavaScriptErrors()` in each browser test. An error in the console is a defect.

### Where a Browser Test Lives and How to Run It

The plugin runs browser tests as normal Pest tests, so they need no separate suite. Put them in `tests/Browser` to separate them from faster tests and run the directory with one command.

- Run a browser test with `vendor/bin/pest tests/Browser`, and add `--parallel` for the complete suite.
- Run `vendor/bin/pest --debug` to open the window of the browser and to pause at a failure. Use `--headed` to watch a run that passes.
- Add `--browser firefox` or `--browser safari` to run the test in a different browser. The default browser is Chrome.
- The run needs Playwright and a browser on the machine. Follow the plugin documentation for local and CI installation commands.
- Fetch `https://pestphp.com/docs/browser-testing` for the interactions, the assertions, and the devices that the plugin gives.

### Browser Test Pitfalls

- The plugin waits five seconds for an element. Raise the value with `pest()->browser()->timeout(10000)` in `Pest.php` for a page that is slower, and do not add a wait for a number of seconds to the test.
- Apply `RefreshDatabase` to the browser tests in `Pest.php`. A browser test hits the application through a real request, and the records that it leaves break the next test.
- Add `tests/Browser/Screenshots` to `.gitignore`. A failure writes a screenshot, and the file is not part of the repository.
- Give `withKeyDown()` a key code, such as `KeyA`. A letter such as `'a'` gives the lowercase character, whatever modifier the test holds.
- Interact inside the callback of `withinFrame()`. An interaction outside the callback does not reach the frame.

## Testing Validation

For request preparation, validated payloads, method-specific rules, redirected old input and sensitive-input exclusions, read [FormRequest contracts](form-requests.md). Keep API JSON refusals distinct from browser session/redirect contracts.

- Write one test for each validation rule when each failure represents a separate contract.
- Write one test with an empty payload to assert several required fields together.
- Assert the text of the message that the user gets. A message that is present but wrong is a defect.
- Use a dataset for input values that need the same setup and the same assertions.

Send an input value that is not valid through the application, and assert the error. Do not assert that an array of rules contains a string, because that assertion tests the declaration and not the behavior. Use such an assertion only for a rule that no request can reach, and write the reason in the test.

### Which Layer Owns Which Case

The rule-class test owns the matrix of values that pass and fail. The endpoint test proves that the endpoint applies the rule and that the user receives the message.

When lower-layer tests own a pure input matrix, recommend consolidation only after identifying the distinct endpoint wiring cases for every applicable handler and HTTP contract. Retain those cases, including different role/route paths, binding/scoping, request preparation and user-visible errors; there is no blanket one-case limit. A rule/policy test still passes when a route omits or miswires that rule. This division also applies to policies, scopes and other request collaborators. Auditing or updating guidance does not authorize deleting established tests.

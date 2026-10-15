---
paths:
  - 'tests/Feature/**'
---

# Feature

## Refresh databases in application test suites
Use RefreshDatabase for Feature tests; keep database reset configuration centralized in tests/Pest.php.

## Controller HTTP contracts
Every concrete controller has an executable Pest test in `tests/Feature/Http/Controllers` at the full mirrored controller path, named `{ControllerName}Test.php`. Abstract controllers are exempt. The controller architecture gate rejects missing, empty, comment-only, or pending-only matching files. The matching file covers every routed action; supplemental shared concerns do not replace it.

Each controller test has one scenario and target action. Split scenario-dependent setup or assertions (guest/member/foreign actors, success/refusal, valid/invalid input) into separate tests; use named datasets only when setup, action and assertion structure stay the same. Do not conceal branches with an expected-status parameter or an assertion callback.

Split independent behaviors that repeat Arrange/Act/Assert cycles. Retain a sequence only when its named defect requires a baseline or earlier action, such as stale input, replay protection or a query comparison; make that necessity explicit. Account for HTTP calls inside setup helpers. Type/null guards should use appropriate assertions, and uniform fixture/collection loops are not separate scenarios by themselves.

Group Arrange by meaning, with blank lines between target relationships, contrast fixtures, actor/context, fakes and payload/baseline preparation where the subject changes. Keep dependent records together and preserve creation order. Do not use blank lines after every statement or a rigid universal setup order.

In Arrange, establish authentication and promotion context, create minimal deterministic fixtures, and configure specific fakes. In Act, assign the HTTP response to `$response`; do not chain assertions onto the request. In Assert, check the response and meaningful persisted state or side effects. Keep target requests outside assertion callbacks.

Format response assertion chains with the response variable on its own line and each chained assertion on a separate following line. Combine consecutive assertions on the same response into one chain instead of repeating the response variable. Keep response assertions together, then leave a blank line before assertions about authentication, persisted state, events, jobs, mail, notifications, or other outcomes.

Use existing named factory states, `count()` and `sequence()` for repeated records, and `for()`/`has()`/`hasAttached()` for relationships when they make the fixture's intent clearer. Keep meaningful variable names, deterministic attributes, deliberate insertion order, and explicit foreign-tenant or invalid fixtures. Do not force different lifecycle states or distinct roles into a sequence merely to shorten setup.

Successful view responses assert the intended view and meaningful data passed to it: exact records, relevant collection identities/order, or payload values. Do not invent data assertions for views whose controllers pass none. Keep distinct rendering, escaping, privacy, and performance regressions. Component presence can share the successful response; detailed component behavior belongs in Integration.

Successful writes assert the response/redirect, relevant flash messages, stored values, and applicable events/jobs/mail/notifications. Refusals assert the intended error, unchanged protected state, and no success side effects. Fake only relevant events after fixtures; assert meaningful event payloads and exact dispatch counts when duplicates are a regression. Fakes do not prove listener execution.

Cover applicable guests, no-membership, insufficient-role, cross-promotion binding, missing/deleted records, validation, and privileged input. A role test needs an active membership so it reaches authorization; a no-membership 403 is a separate contract. Keep full business matrices at lower layers and purposeful HTTP wiring cases here.

## Middleware HTTP contracts
Every concrete application middleware under `app/Http/Middleware` has an executable Pest test at its full mirrored path under `tests/Feature/Http/Middleware`, named `{MiddlewareClassName}Test.php`. Genuine abstract bases are exempt. Framework/vendor middleware are excluded; purposeful tests of application configuration and registration remain valuable. The architecture gate rejects missing, misplaced, malformed, empty, comment-only and pending/skip-only matching files. Meaningful HTTP behavior is reviewed separately from declaration presence.

Use the same explicit Arrange/Act/Assert, `$response`, multiline response chains and fixture grouping as controller HTTP tests. Prove continuation or refusal and relevant session, authentication, context and response-header effects. Preserve necessary cookie/session replay, request-context isolation and throttle baselines with a visible prerequisite and target request. Custom middleware use action/purpose names without a `Middleware` suffix; naming describes responsibility and does not require every class to start with `Ensure`.

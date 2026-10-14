---
paths:
  - 'tests/Feature/**'
---

# Feature

## Refresh databases in application test suites
Use RefreshDatabase for Feature tests; keep database reset configuration centralized in tests/Pest.php.

## Controller HTTP contracts
Every concrete controller has an executable Pest test in `tests/Feature/Http/Controllers` at the full mirrored controller path, named `{ControllerName}Test.php`. Abstract controllers are exempt. The controller architecture gate rejects missing, empty, comment-only, or pending-only matching files. The matching file covers every routed action; supplemental shared concerns do not replace it.

In Arrange, establish authentication and promotion context, create minimal deterministic fixtures, and configure specific fakes. In Act, assign the HTTP response to `$response`; do not chain assertions onto the request. In Assert, check the response and meaningful persisted state or side effects. Keep target requests outside assertion callbacks.

Format response assertion chains with the response variable on its own line and each chained assertion on a separate following line. Keep response assertions together, then leave a blank line before assertions about authentication, persisted state, events, jobs, mail, notifications, or other outcomes.

Successful view responses assert the intended view and meaningful data passed to it: exact records, relevant collection identities/order, or payload values. Do not invent data assertions for views whose controllers pass none. Keep distinct rendering, escaping, privacy, and performance regressions. Component presence can share the successful response; detailed component behavior belongs in Integration.

Successful writes assert the response/redirect, relevant flash messages, stored values, and applicable events/jobs/mail/notifications. Refusals assert the intended error, unchanged protected state, and no success side effects. Fake only relevant events after fixtures; assert meaningful event payloads and exact dispatch counts when duplicates are a regression. Fakes do not prove listener execution.

Cover applicable guests, no-membership, insufficient-role, cross-promotion binding, missing/deleted records, validation, and privileged input. A role test needs an active membership so it reaches authorization; a no-membership 403 is a separate contract. Keep full business matrices at lower layers and purposeful HTTP wiring cases here.

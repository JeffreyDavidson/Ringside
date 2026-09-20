# Ringside project audit and remediation plan

Date: 2026-09-19. Reviewed checkout: `1c6fea627`, branch `chore/standardize-project-tooling`.
Canonical planning card: Ringside Hermes Kanban `t_efa022de`. This document is the evidence and proposed sequence; execution status belongs on the board. No implementation workers were dispatched.

## Scope and confidence

Repository-wide inventory and targeted flow review covering application architecture, authentication/authorization, Livewire, rendering, domain transactions, migrations, tests, static analysis, dependency manifests, frontend build, CI, hooks, documentation, and operations readiness. Inventory: 470 application files, 452 test files, 107 migration files, and 163 view files. These are file counts, not proof of full behavioral coverage.

This is a broad source audit, not a claim that every line or runtime scenario has been verified. Each finding below identifies whether it is established from source/checks, requires reproduction, or needs a product decision. No production access, live database queries, penetration test, restore exercise, browser/accessibility pass, or load test was performed. PHP application tests/static analysis need a reproducible dependency environment before their current results can be trusted. No application code or dependencies were changed during this audit; the Vite build regenerated ignored build artifacts.

The checkout is two commits beyond the locally recorded `origin/develop` (`b3d92525c`), and its remote branch is marked gone. This is not a fresh remote synchronization claim. Preserve those commits when choosing the implementation base.

## Verification performed

| Check | Result and practical limit |
| --- | --- |
| `composer validate --strict --no-check-publish` | Passed. |
| `composer install --dry-run --no-scripts --no-interaction` | Reports 9 missing packages and 21 version changes needed to match the lockfile. No installation performed. |
| `composer audit --locked --format=plain` | Passed; no known security advisories at audit time. |
| `npm audit --json` | Passed; zero reported vulnerabilities. |
| `npm run lint` | Passed. |
| `npm run format:check` | Passed for configured JS scope. |
| `npm run build` | Passed; unresolved `/images/bg-10.png` warning. Broad media glob emits hundreds of assets; manifest approximately 96 kB. |
| Prior CI evidence | Merged PR #1589 passed with level 9 and existing analysis policy. That does not validate the two subsequent local commits or maximum-level settings. |

## Confirmed findings and directly actionable work

### A01 — P1: Installed PHP dependencies do not match the lockfile

Evidence: dry-run above; activitylog 5.1.1, Nightwatch, Head, Pennant and tenancy packages are among missing installs. Installed Pest/Livewire and other packages also differ. This explains why earlier local runs failed to load the activitylog trait while locked CI could boot. It does not establish the cause of all PHPStan errors.

**Fix:** establish an isolated checkout with `composer install` from the committed lockfile and `npm ci`. Do not use `composer update`, fabricate compatibility stubs, or edit application code to accommodate this vendor drift. Preserve current local work and private environment files.

**Acceptance:** install dry-run reports no changes; Laravel boots in testing; app and Pest analysis run to completion with current committed settings; collect machine-readable results before changing strictness. Run canonical application checks and affected browser checks there. Dependency versions and source revision accompany every subsequent result.

### A02 — P1: Password recovery cannot complete its advertised flow

Evidence: `routes/auth.php` provides request-link routes but no `password.reset` route or reset submission route. `PasswordResetLinkController::store()` calls the broker. No custom reset URL callback was found in app/routes. Laravel's installed `ResetPassword::resetUrl()` generates `route('password.reset', ...)`. The test in `tests/Feature/Http/Controllers/Auth/PasswordResetLinkControllerTest.php` fakes notifications and never renders the email URL or consumes the token.

**Fix:** implement reset-token form and password update using Laravel's broker and Form Requests. Keep notification delivery covered without letting the fake hide URL generation.

**Acceptance:** render actual notification mail to generate its link; visit that link; update the password; verify old credentials fail and new credentials work; invalid, expired, reused token and confirmation mismatch cases pass. Add request throttling consistent with the chosen public-auth policy.

### A03 — P1: Frontend initialization registers its listener too late

Evidence: `resources/js/app.js` calls `Livewire.start()` before registering `livewire:init`. Installed `vendor/livewire/livewire/dist/livewire.esm.js` emits this event synchronously inside `start()`. The late listener contains the Alpine UI registration and sidebar store. Sidebar/header templates depend on that store, with guards that can hide its absence.

**Fix:** initialize Alpine plugins/store before Livewire startup using its supported bundling API. Reconfirm against the locked Livewire version after A01. Remove development console logging from both app/auth entry points.

**Acceptance:** actual browser checks for desktop collapse/expand, mobile open/close, Escape, and navigation; assert the store exists and no JS errors occur. Build and ESLint must pass. A screenshot alone is insufficient.

### A04 — P1: Match-result modal authorizes saving, but not loading its records

Evidence: `app/Livewire/Matches/Modals/ResultModal.php` loads a caller-selected match and related competitors in `mount()`/`match()`; authorization occurs only in `save()`. The existing unauthorized test constructs the modal successfully and checks denial only after calling save. A locked identifier prevents later tampering but does not authorize the initial identifier.

**Fix:** enforce the existing view/update policy at the data-loading boundary; review other modal mount/computed paths for the same pattern. This is a confirmed missing check; external exploitability through the deployed Livewire endpoint still requires reproduction.

**Acceptance:** guest/basic users cannot mount or render protected match information; authorized users can. Test actual Livewire endpoint behavior and direct component construction, guessed IDs, and subsequent requests. Keep mutation authorization immediately before saving.

### A05 — P2: Auth layout references an unresolved asset

Evidence: `resources/views/components/layouts/auth.blade.php:28` uses `/images/bg-10.png`; the build reports it unresolved and the corresponding public file is absent. A hashed `bg-10` media asset is emitted by Vite.

**Fix:** reference the owned media asset through the established Vite pipeline, or remove the background if intentionally retired.

**Acceptance:** built login/register/recovery pages have no asset 404, and the background works at mobile/desktop sizes.

### A06 — P2: The previous PHPStan diagnosis was not demonstrated

The earlier changes simultaneously raised level 9 to `max`, changed PHPDoc certainty, removed a redundant-expectation ignore, removed test type-coverage thresholds, and introduced helper stubs. Reverting multiple variables and observing green CI cannot establish that the stubs caused 1,000+ errors. The earlier explanations attributing them to the stubs or suite size were too confident.

PHPStan documents that level 10 checks implicit `mixed` in addition to level 9's explicit `mixed`: [rule levels](https://phpstan.org/user-guide/rule-levels). PHPDoc certainty is a separate option: [configuration reference](https://phpstan.org/config-reference#treatphpdoctypesascertain). Treating `false` as universally better was not justified either.

**Fix/investigation:** after A01, run app and Pest separately with JSON output. Compare baseline, level-only change, certainty-only change, ignore-only change, threshold-only change, and stub-only additions. Keep all other inputs fixed and use distinct result caches. Group errors by root API, not number of messages. Inspect factory generics, collection callbacks, modal identifiers and helper returns before editing individual tests.

**Acceptance:** reproducible before/after counts for each variable; tests prove actual contracts; original app findings and test findings are resolved in small slices. Do not count restoration of level 9 as fixing the stronger-analysis failures. Do not use broad ignores, invented types or casts to manufacture a pass. Decide an explicit level and test type-coverage target before enforcement.

### A07 — P2: Coverage reports omit major application boundaries

Evidence: `phpunit.xml` excludes `app/Livewire`, `app/Console` and provider files from source coverage; coverage threshold is 44 in Composer and the manual coverage workflow. `test:coverage` aliases `test:unit`, whose implementation is not limited to the Unit suite. Type coverage is a separate metric and does not demonstrate behavioral coverage.

**Fix:** make command names/scope truthful and publish current coverage by domain. Decide exclusions and thresholds using measured numbers; prioritize authentication, unauthorized reads, scheduling/result persistence, and sidebar behavior over numerical targets.

**Acceptance:** a reproducible coverage report states included/excluded code; commands match documentation; agreed thresholds are enforced consistently. Restore/retain functional tests when replacing redundant type assertions.

### A08 — P2: Shared instructions contain conflicting service ownership rules

Evidence: `.ai/rules/services.md` says services establish/synchronize relationship records and also says services are read-only. The user's settled rule and architecture backlog assign writes to Actions. Actual `StableMembershipService` is already read-only.

**Fix:** use Boost's rule-recording mechanism to remove obsolete write-service guidance; reconcile architecture examples against current behavior. Do not refactor already-correct services or introduce replacement wrappers.

**Acceptance:** instructions consistently assign reads/calculations/validation to Services and writes/orchestration to Actions, preserving focused lifecycle mechanics. No contradictory rule remains.

### A09 — P2: CI documentation contains stale and unsafe local instructions

Evidence: `docs/workflows/ci-cd.md` claims PHPStan-result caching absent from the inspected workflow, describes 512M where setup uses 4G, misstates triggers, and recommends copying `.env.testing` over `.env`. That command would overwrite local configuration.

**Fix:** rewrite commands/triggers/cache and branch-protection claims from actual workflow/API evidence. Use an isolated test environment without overwriting the developer's environment file. Resolve stale architecture backlog status while doing the documentation pass.

**Acceptance:** documented commands are verified in a disposable checkout; no claim that a feature-branch push alone triggers CI where only integration/release branch pushes and PRs do; protection claims verified remotely before publication.

### A10 — P3: Development tooling still has small scope/ownership inconsistencies

Evidence: `.ai/skills/pest-plugin-agent/SKILL.md` remains tracked despite the selected `.agents/skills` convention; `npm run format:check` covers resource JS only, not root config files; Vite includes analysis for any truthy `ANALYZE` value; `.githooks/pre-push` runs the entire `test:push` pipeline with raw output.

**Fix:** identify the skill producer before migrating/removing it; align formatter ownership (Pint owns Blade here); make analyzer activation explicit if needed. Capture full check logs in files and emit short summaries, preserving meaningful verification and CI gates.

**Acceptance:** no duplicate skill source or regeneration regression; checks cover intended file types; a failed hook produces actionable concise output and a log location. Do not silently bypass hooks to save tokens.

## Risks requiring reproduction before a code fix

### A11 — P1 investigation: Database concurrency and engine coverage

Evidence: `AddMatchForEventAction` locks an event before existing matches/resources; `RecordResultAction` locks a match before its event; conflict services additionally lock events. `Events/UpdateAction` specifies three transaction attempts while several match actions use the default. These are potential contention/lock-order concerns, not a demonstrated deadlock. Migration code has engine-specific filtered/generated unique-index branches; CI setup explicitly supports SQLite and no service database matrix was found.

**Plan:** identify the supported production database, run migrations on it, and reproduce simultaneous booking, event rescheduling, result recording and championship changes with multiple connections. Trace actual lock graphs. Standardize order and retries only where justified.

**Acceptance:** competing operations preserve no-double-booking and unique-open-period invariants; one valid result is committed or a clear retryable/domain rejection is returned. Fresh-install and upgrade migrations pass on the supported engine. Never edit merged migrations to repair data.

### A12 — P2 investigation: HTML column safety and contracts

Evidence: the table view intentionally renders HTML; `Column::linkHtml()` escapes title/URL, which is good. `ArrayColumn` always marks itself HTML but its plain-item fallback implodes values without escaping. Reviewed referee/title usages use escaped link callbacks, so a reachable XSS defect has not been established. `LinkColumn` and `ArrayColumn` callbacks also lack precise contracts, matching earlier maximum-level analysis findings.

**Plan:** inventory every HTML callback; add hostile-label rendering tests and precise callback/result types. Escape plain values and explicitly distinguish trusted markup. Do not label the application vulnerable solely from raw Blade output.

**Acceptance:** user-controlled names/previews display as text; known links render correctly; arbitrary URL schemes cannot enter links through supported input paths; static analysis can follow callback return types.

### A13 — P2 investigation: Performance and asset footprint

Evidence: `import.meta.glob(['../media/**'])` emits a broad template-media inventory; local build emitted hundreds of assets. This measures build/deployment footprint, not browser transfer of all assets. Some domain reads correctly eager-load; no request profiling was performed.

**Plan:** measure representative roster tables, event cards and championship histories with realistic data; record query counts, pagination behavior and payload sizes. Inventory referenced media before deletion, then retain only needed assets. Use Debugbar for local request profiling when the environment is reproducible.

**Acceptance:** documented baseline and target; no relationship-query growth per rendered row; referenced assets survive; measured improvements justify caches/indexes. Avoid speculative caching of mutable lifecycle state.

## Decisions to discuss

| ID | Priority | Decision and evidence | Recommended discussion outcome |
| --- | --- | --- | --- |
| D01 | P1 | Registration is public; new users are Basic/Unverified; login does not filter Active/Inactive and routes lack an account-status gate. `UserStatus` includes Inactive. | Decide public signup versus invitation and the meaning of inactive/unverified. Then test login, existing sessions, verification and throttling for that policy. Do not silently lock existing users out. |
| D02 | P1 before multiple customers | Promotion isolation is explicitly a future plan in `docs/guides/promotion-management-plan.md`; installing tenancy does not implement it. Policies currently use a global administrator bypass. | Confirm single-organization use today versus multi-promotion delivery. Define ownership, membership, global admins and cross-promotion operations before schema changes. |
| D03 | P2 | Maximum PHPStan level, PHPDoc certainty, redundant-expectation policy and test parameter coverage are separate choices. | Pick targets after A06 measurements. Prefer incremental typed contracts over adding broad suppressions or copying another app's config. |
| D04 | P1 before production expansion | Repository search found aspirational operational notes but no verified restore/deployment runbook. No production inventory was made. | Confirm hosting, database engine, backup owner/retention, recovery time/data-loss targets, monitoring and deployment/forward-only-migration recovery procedure. This is missing evidence, not proof that backups are absent. |
| D05 | P2 | User mass assignment includes `role`, `status`, and `email_verified_at`; inspected registration explicitly chooses fields and forces Basic. No public escalation path was demonstrated. | Define privileged-field write boundaries and add tampering tests to every user form before deciding whether to narrow fillable fields. |
| D06 | P2 | Coverage excludes Livewire; security scanning is advisory; pre-push runs expensive suites. | Agree release gates, supported database coverage, browser journeys, coverage scope and local-vs-CI responsibilities. Preserve enforceable protection while reducing output/noise. |

## Ordered implementation slices

| Order | Slice | Depends on | Completion gate |
| --- | --- | --- | --- |
| 0 | Reconcile local branch and reproduce locked environment (A01) | None | Clean reproducible install; baseline tests/analysis recorded; local commits preserved. |
| 1 | Complete recovery flow (A02) and frontend startup/assets (A03/A05), separate PRs | 0 | Functional regression tests and browser journey checks. |
| 2 | Read authorization and user-field tampering audit (A04/D05) | 0 | Negative read/write tests; no unauthorized record payloads. |
| 3 | Resolve signup/status and promotion-scope decisions (D01/D02) | Discussion | Written accepted behavior before implementation. |
| 4 | Controlled PHPStan comparison and shared-type corrections (A06/D03) | 0 | Root-cause matrix; per-slice error reductions; target configuration passes app AND test analysis. |
| 5 | Production-engine concurrency/migrations and coverage scope (A07/A11) | 0, D04/D06 | Multi-connection tests and coverage artifacts. |
| 6 | HTML contracts, request profiling and browser/accessibility sweep (A12/A13) | 0, 1 | Hostile-input tests, query baselines, keyboard/mobile journeys; performance changes supported by evidence. |
| 7 | Rules/docs/skills/check-output cleanup (A08–A10) | Findings above | Accurate guidance; one skill source; usable logs; formatter ownership consistent. |
| 8 | Operations readiness (D04) | Operational decisions | Reviewed runbook and demonstrated restore in an isolated environment; separately authorized deployment. |

Keep PRs focused and based on verified develop state. Each implementation card should reference these IDs, list its test gate, and state any remaining discussion dependency. A successful merge is not a substitute for reproducing the reported failure. No production changes, dependency upgrades, mass refactors, automatic merges or task dispatch are authorized by this planning document.

## Positive findings to preserve

Routes have extensive policy middleware; Livewire saves generally reauthorize; registration explicitly assigns Basic role; passwords use the hashed cast; login throttles attempts and rotates sessions; dummy-data actions enforce local/testing environments. Domain operations already use transactions, row locks and uniqueness constraints. Services are small rather than generic repositories. CI has bounded jobs, formatting/static-analysis/browser/dependency checks, and least-privilege contents access in the main workflow. Composer/npm audits and JS checks passed. These are useful foundations; the plan repairs identified gaps rather than replacing the architecture.

## Remaining audit validation

After A01, run the full canonical checks and review failures at the locked revision. Complete live browser/a11y testing, actual unauthorized HTTP/Livewire requests, concurrency on the supported engine, representative query profiling and staging restore/deployment verification. Record results on the board and update confidence/severity for A04/A11/A12/A13. No finding here claims exhaustive production security assurance or complete domain correctness.

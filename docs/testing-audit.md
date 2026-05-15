# Ringside Testing Architecture & Quality Audit

**Issue:** INF-56 — Ringside: testing architecture and quality audit  
**Repo/branch audited:** `/Users/jeffreydavidson/.openclaw/workspace/ringside-app` on `development`  
**Audit date:** 2026-05-13  
**Updated:** 2026-05-15 after INF-62 Laravel 13 baseline alignment  
**Standard:** `/Users/jeffreydavidson/.openclaw/workspace/docs/testing-quality-standard.md`


## Current Baseline Status — 2026-05-15

Jeffrey confirmed Ringside's intended framework baseline is **Laravel 13 / Livewire 4**. INF-62 was completed by PR #633, which aligned CI/docs with that baseline and stopped CI from mutating dependency constraints during the run.

What changed after the original audit:

- `composer.json` remains the source of truth: `laravel/framework ^13.7`, `livewire/livewire ^4.0`.
- CI/PCOV now target Laravel 13 and use `composer install` against the committed dependency set instead of `composer require laravel/framework:12.*` during CI.
- Local verification can boot far enough to run the Pest suite under Laravel 13.
- The suite is **not green yet**: the Laravel 13 baseline exposes known failures that are tracked separately.

Follow-up tickets created from the exposed failure buckets:

- INF-103 — model metadata/fillable assertions for Laravel 13.
- INF-104 — Livewire table `Column` expectations for Livewire 4.
- INF-105 — TagTeam trait expectation drift.
- INF-106 — remaining Laravel 13 verification baseline failures.

This audit should be read as the testing architecture roadmap, not as proof that the current test suite is launch-ready.

## Executive Summary

Ringside has a large and valuable Pest test suite, but it is not yet aligned with Jeffrey's cross-project testing standard. The suite is strongest around model configuration, status/lifecycle actions, authorization, Livewire table/modal rendering, and workflow smoke coverage. The biggest launch-readiness risks are:

1. **The suite is broad but uneven:** 299 `*Test.php` files and ~4,410 `test()` / `it()` calls, but only a partial 1:1 mirror of production classes.
2. **Type boundaries are blurred:** many `Unit` tests require Laravel/database behavior; several `Feature` workflow tests are effectively Livewire integration tests; Browser tests include a default Laravel example.
3. **First-class categories are incomplete:** Architecture exists, Browser exists but is thin, Static Analysis exists in scripts/CI, but Contract, Snapshot/Approval, Performance, Accessibility, and Visual testing are not materially represented.
4. **Architecture tests are currently too generic and partially stale:** several rules reference namespaces/patterns that do not match this app (`Repositories`, `Interfaces`, `Contracts` suffix, model usage only in repositories), and there is no rule enforcing required class/test pairing.
5. **Original local verification was blocked:** PHP was not available in the first audit environment and `vendor/` was missing. After INF-62, local verification can boot with Herd PHP/Composer, but the Laravel 13 suite still exposes known failures tracked in INF-103–106.

## Evidence Collected

### Configuration and gates

- `composer.json` declares Pest, Pest Browser, Larastan, Pint, Rector, type coverage, PHP 8.4.
- `phpunit.xml` defines suites: `Feature`, `Unit`, `Browser`, `Integration`.
- `phpunit.dusk.xml` exists for browser suite with `APP_URL=http://ringside.test`.
- `.github/workflows/ci.yml` runs Feature, Integration, Unit, and PHPStan against Laravel 13 after INF-62 / PR #633.
- `.github/workflows/run-tests-pcov-pull.yml` runs Feature/Integration/Unit with coverage against Laravel 13 after INF-62 / PR #633.
- `composer test` runs type coverage, Rector dry-run, Pint test, PHPStan, and Pest with coverage.
- Local verification uses Herd PHP/Composer in this workspace; the suite currently boots but has known Laravel 13 failure buckets tracked in INF-103–106.

### Suite inventory

Current `*Test.php` files by top-level suite/path:

| Area | Files |
|---|---:|
| `tests/Unit` | 130 |
| `tests/Integration` | 121 |
| `tests/Feature` | 44 |
| `tests/Browser` | 3 |
| Root Architecture test | 1 |
| **Total** | **299** |

Approximate test/it calls: **4,410**.  
Approximate `->group()` calls: **19**, despite project docs requiring every test to have groups.

Production inventory: **475** `app/**/*.php` files.  
Crude 1:1 mirror check against `tests/{Feature,Integration,Unit}/<app path>/*Test.php` found **189 covered** and **286 missing**. This is not a semantic coverage result, but it highlights navigation/predictability gaps under the cross-project standard.

Notable missing 1:1 areas by production path:

| Production area | Files | Missing 1:1 tests |
|---|---:|---:|
| `Actions` | 107 | 53 |
| `Http/Controllers` | 23 | 3 |
| `Livewire` | 95 | 54 |
| `Models` | 110 | 55 |
| `Rules` | 16 | 16 |
| `Services` | 8 | 8 |
| `Data` | 12 | 12 |
| `Enums` | 13 | 13 |
| `Exceptions` | 41 | 41 |
| `Policies` | 11 | 1 |
| `Builders` | 20 | 12 |
| `View` | 7 | 7 |
| `Console` | 3 | 3 |

Representative missing/high-value paths:

- `app/Actions/Matches/AddMatchForEventAction.php`
- `app/Actions/Matches/AddRefereesToMatchAction.php`
- `app/Actions/Matches/AddTagTeamsToMatchAction.php`
- Most `app/Actions/Stables/*` beyond lifecycle/retire/split coverage
- Most `app/Actions/Titles/*` beyond activate/create/update coverage
- All `app/Services/*`
- All `app/Data/*`
- All `app/Rules/*` by strict mirrored path, even where similarly named unit tests exist with `UnitTest` suffix
- All domain exceptions by strict mirrored path
- Many Livewire base/concern/table/form classes

## Alignment to Jeffrey's Testing Standard

### 1. Mirrored test directory/namespace and 1:1 class-to-test expectations

**Status: Partially aligned.**

Strengths:

- Many core models have mirrored tests, e.g. `app/Models/Wrestlers/Wrestler.php` → `tests/Unit/Models/Wrestlers/WrestlerTest.php`.
- Many action classes have mirrored integration tests, especially roster employment/status actions.
- Controllers mostly have mirrored feature tests under `tests/Feature/Http/Controllers/...`.
- Arch tests are correctly treated as a grouped exception at `tests/ArchitectureTest.php`.

Gaps:

- 1:1 coverage is not systematic across Actions, Livewire, Rules, Services, Data, Enums, Exceptions, View components, Console commands, and some Builders/Models.
- Some test names break predictable mirroring, e.g. `DateCanBeChangedUnitTest.php` instead of `DateCanBeChangedTest.php` under the right suite already communicates the type.
- Factory tests live under `tests/Unit/database/Factories/...` with lowercase `database`, which does not mirror the PSR namespace `Database\Factories` or the project root folder casing.
- Existing Architecture tests do **not** enforce class/test pairing.

Recommendation: add an Architecture/static test that reports production classes without a mirrored test, with explicit allowlists for Arch tests, tiny glue, generated/framework-only code, and cross-cutting workflow tests.

### 2. Feature tests = high-level behavior/workflow

**Status: Mixed.**

Strengths:

- Authentication, navigation, event/title/roster/wrestler workflows exist.
- Authorization tests cover user-visible access behavior.
- Controller tests are located under the expected feature path.

Gaps:

- Several `Feature/Workflows` tests drive Livewire components directly and assert persistence; that is closer to integration than HTTP/user workflow behavior.
- Some workflow tests contain comments like “assuming this functionality exists,” which means they are not fully trusted as executable behavioral guarantees.
- Feature coverage for end-to-end promotion workflows is shallow compared to domain breadth: event booking, title matches, competitors, officials, contracts, venues, computed statuses, stable/tag-team membership, and match result workflows should be clearer.

### 3. Integration tests = classes/modules working together

**Status: Strongest area, but uneven.**

Strengths:

- Good action integration coverage for wrestlers/managers/referees/tag teams employment, injury, suspension, retirement, release, restore, and reinstatement.
- Good Livewire integration coverage for tables/modals in several domains.
- Seeder and builder integration tests exist.
- Domain workflows for employment exist for managers/referees/tag teams/wrestlers.

Gaps:

- Matches coverage lacks full parity: `AddCompetitorsToMatchAction`, `AddTitlesToMatchAction`, and `AddWrestlersToMatchAction` are covered, but `AddMatchForEventAction`, `AddRefereesToMatchAction`, and `AddTagTeamsToMatchAction` are not mirrored.
- Events, Venues, Titles, and Stables action coverage is incomplete compared with production action surface.
- Service classes are untested despite encoding membership/lifecycle/validation behavior.
- There is no clear integration suite for contracts, booking constraints across bookable competitors/officials, or computed status interactions spanning promotions/events/matches/titles.

### 4. Unit tests = low-level isolated logic

**Status: Quantity high; isolation quality mixed.**

Strengths:

- Many model tests verify fillables, casts, traits, interfaces, relationships, builders, and computed statuses.
- Builders, policies, some rules, validation helpers, and table/base concerns have unit coverage.
- Domain-specific custom expectations exist.

Gaps:

- `tests/Unit` uses `RefreshDatabase` globally, which makes the whole Unit suite framework/database-coupled by default.
- Many model “unit” tests instantiate Eloquent builders, traits, factories, or relationships. Useful, but not isolated unit tests by the cross-project definition.
- Good candidates for true unit coverage are missing or incomplete: enums, value objects (`Height`), casts (`HeightCast`), data DTOs, date helper, exception factories/messages, match decision/type logic, status priority logic.

Recommendation: keep existing structural tests if they are valuable, but classify database-dependent tests as Integration over time and reserve Unit for pure/isolated logic.

### 5. Browser / E2E

**Status: Present but not production-ready.**

Files:

- `tests/Browser/DashboardTest.php`
- `tests/Browser/LoginTest.php`
- `tests/Browser/ExampleTest.php`

Gaps:

- `ExampleTest.php` asserts default Laravel content at `/` and should be removed or replaced.
- No browser coverage for critical Livewire/JS workflows: creating/editing roster members, event/match booking modal behavior, dynamic match type UI, title management, table filtering/search/action dropdowns, or onboarding/login-to-dashboard journey beyond basics.
- Browser suite is not included in the main GitHub CI workflows seen during audit.

### 6. Architecture tests

**Status: Exists, needs hardening.**

Strengths:

- Uses Pest Arch presets and checks strict types, controller conventions, action suffixes, debug helpers, facades, and commands.

Gaps / stale rules:

- Rules reference `App\Repositories` and `App\Interfaces`, which appear absent.
- `contracts directories only contain interfaces with Interface suffix` conflicts with actual contracts like `Bookable`, `Employable`, `Retirable`, etc.
- `models are only used in repositories` conflicts with an Eloquent Laravel app where actions, policies, factories, Livewire, and tests naturally use models.
- No Arch test enforces mirrored class-to-test expectations.
- No Arch rule around Ringside domain boundaries: actions vs models vs Livewire vs controllers, computed status not stored, bookable competitor/official interfaces, or domain exception placement.

### 7. Contract tests

**Status: Not materially present.**

Ringside does not appear to have external APIs/webhooks yet, but contract tests are still useful for:

- Internal route response shapes for pages/controllers if JSON endpoints are added.
- Import/export payloads when added.
- Queue/job payloads when background processing appears.
- Stable shape of Livewire form state for match booking (`matchType`, competitors, referees, titles, decisions/results).

### 8. Snapshot / approval tests

**Status: Not present.**

Good candidates:

- Generated match cards/event rundowns.
- Title lineage/championship summary output.
- Emails, exported CSV/JSON/PDF when added.
- Rendered summaries for roster profiles, title histories, contracts.

### 9. Static analysis gates

**Status: Present, but consistency risk.**

Strengths:

- PHPStan/Larastan configured at level 6.
- Composer scripts include Pint, Rector dry-run, PHPStan, Pest type coverage, and 100% test coverage/type coverage gates.
- CI runs PHPStan.

Risks:

- PHPStan excludes tests, so test code quality/types are not statically checked.
- `phpunit.xml` excludes `app/Livewire`, `app/Console`, and providers from coverage source. That may hide important UI/application behavior from coverage thresholds.

### 10. Performance tests

**Status: Not present as first-class tests.**

High-value Ringside candidates:

- Table/list pages for wrestlers, tag teams, stables, titles, events, venues: query count/N+1 guards.
- Event show + match card construction query budgets.
- Title championship history and longest reigning champion summary query budgets.
- Bookable competitor/official selectors at realistic roster sizes.

### 11. Accessibility / visual tests

**Status: Not present as first-class tests.**

High-value Ringside candidates:

- Login/register/dashboard.
- Roster index/show pages.
- Event/match booking modal.
- Tables with action dropdowns, filters, badges, and status columns.
- Responsive navigation/sidebar.

## Ringside Domain Coverage Assessment

| Domain / concept | Current signal | Main gaps |
|---|---|---|
| Wrestlers | Strong model/action/workflow/table coverage | Browser coverage; true unit tests for computed status edge cases; contracts/bookability across matches |
| Managers | Strong action and table coverage | Manager assignment service coverage; workflow assertions should prove actual assignment behavior |
| Referees | Good action/table coverage | Match officiating/bookable official rules and `AddRefereesToMatchAction` missing |
| Tag teams | Good employment/action/model coverage | Add-to-match action missing; membership service/lifecycle service tests missing |
| Stables | Some lifecycle/retire/split coverage | Many actions missing 1:1 tests; membership/orchestrator/service coverage incomplete |
| Titles | Some activate/create/update/model coverage | Debut/deactivate/pull/reinstate/retire/unretire/delete coverage incomplete; championship summaries/snapshots missing |
| Events | Workflow + lifecycle coverage | Create/update/delete/restore action parity; event-match booking flow needs stronger feature/browser coverage |
| Matches | Some action/rule/dynamic UI coverage | Full match booking flow, referees/tag teams/titles/result/decision contracts, performance/query guards |
| Venues | Controller/workflow/seeder coverage | Action parity and venue-event relationship behavior needs more explicit integration coverage |
| Contracts | Mentioned as core feature but no obvious app surface/test coverage in audited tree | Define production surface and add contract/feature/integration tests when implemented |
| Computed status | Well represented in factories/actions/models | Need Arch/static guard that status is not stored; focused unit matrix for priority order across domains |
| Bookable competitors/officials | Some traits/contracts/rules exist | Contract-style matrix for Wrestlers/TagTeams/Referees; managers explicitly not bookable |

## Style and Organization Problems

1. **Unit suite is not isolated.** `RefreshDatabase` is applied to Unit tests globally.
2. **Groups are documented as mandatory but rarely used.** Only 19 group calls across ~4,410 tests.
3. **Some tests assert implementation structure more than behavior.** Trait/interface/fillable checks are useful as architecture/structural guards, but they should not crowd out behavior tests.
4. **Generic exceptions appear in business-rule tests.** Many action tests assert `Exception::class` instead of domain-specific exceptions, reducing regression precision.
5. **Default/scaffold tests remain.** `tests/Browser/ExampleTest.php` should be removed/replaced.
6. **Feature/Integration boundaries are fuzzy.** Livewire component interactions often live in Feature when they are closer to Integration.
7. **Architecture tests are partially stale.** Some rules likely encode a previous architecture and may produce false confidence or false failures.
8. **Naming drift hurts navigation.** Examples: `*UnitTest.php`, lowercase `tests/Unit/database`, lifecycle catch-all files, and tests that cover multiple production classes without clear per-class mirrors.
9. **Coverage gates may be misleading.** Main source excludes Livewire/Console/providers, while Livewire is a major part of the app.
10. **Laravel 13 baseline failures are now visible.** INF-62 aligned docs/CI with Laravel 13, and the remaining failures are tracked in INF-103–106 instead of being hidden by a framework downgrade.

## Recommended Test Improvement Tickets

### P0 — Must fix before treating suite as a launch-quality gate

1. **INF-56-P0-A: Keep test architecture executable and truthful**
   - Laravel version mismatch was corrected by INF-62 / PR #633: Laravel 13 / Livewire 4 is the intended baseline.
   - Continue requiring CI to install the intended committed dependency set without mutating dependencies unexpectedly.
   - Decide whether Browser tests are required in CI or explicitly separate.
   - Work through the Laravel 13 failure buckets tracked in INF-103–106.

2. **INF-56-P0-B: Replace stale Architecture rules and add mirror enforcement**
   - Remove or update stale `Repositories`, `Interfaces`, `Contracts Interface suffix`, and `models only used in repositories` rules.
   - Add an Arch/static test for required production class → test class pairing.
   - Add allowlist with reasons for Arch tests, tiny glue, framework-only files, and intentional cross-cutting workflow tests.

3. **INF-56-P0-C: Reclassify Unit vs Integration boundaries**
   - Stop applying `RefreshDatabase` globally to `tests/Unit`.
   - Move database/Eloquent relationship tests that need DB into `Integration`.
   - Keep true units for enums, value objects, casts, helpers, DTOs, pure rules, exception factories/messages, and status priority logic.

4. **INF-56-P0-D: Close critical domain action parity gaps**
   - Add mirrored integration tests for match/event/title/stable/venue actions that currently lack 1:1 coverage, especially:
     - `Actions\Matches\AddMatchForEventAction`
     - `Actions\Matches\AddRefereesToMatchAction`
     - `Actions\Matches\AddTagTeamsToMatchAction`
     - missing `Actions\Titles\*` lifecycle actions
     - missing `Actions\Stables\*` lifecycle/member actions
     - missing `Actions\Events\*` and `Actions\Venues\*`

5. **INF-56-P0-E: Replace scaffold Browser test**
   - Delete/replace `tests/Browser/ExampleTest.php`.
   - Add at least one real smoke E2E: login → dashboard → roster list or event list.

### P1 — High-value hardening

1. **INF-56-P1-A: Add service and domain collaboration integration tests**
   - `StableLifecycleService`, `StableMembershipService`, `StableValidationService`
   - `TagTeamLifecycleService`, `TagTeamMembershipService`, `TagTeamValidationService`
   - `WrestlerManagerAssignmentService`
   - `ErrorMessageMappingService`

2. **INF-56-P1-B: Add computed status and bookability matrices**
   - Status priority: retired > employed > future employment > released > unemployed.
   - Injured/suspended interactions across wrestlers/managers/referees/tag teams.
   - Bookable competitors: wrestlers/tag teams.
   - Bookable officials: referees.
   - Managers explicitly not bookable.

3. **INF-56-P1-C: Add high-level feature workflows for core promoter jobs**
   - Create roster member → employ/suspend/retire/reinstate.
   - Create tag team from wrestlers → book into event match.
   - Create event at venue → add match → assign competitors/referees/title → record result.
   - Create title → debut/activate → defend in match → view championship history.

4. **INF-56-P1-D: Add Browser/E2E coverage for JS/Livewire-critical flows**
   - Dynamic match type UI in the real browser.
   - Livewire modals/forms for roster/event/match/title flows.
   - Table filters/search/action dropdowns for one representative domain, then expand.

5. **INF-56-P1-E: Add performance query guards**
   - Query count budgets for roster tables, event show/matches, title history, and selector components at realistic dataset sizes.

6. **INF-56-P1-F: Make Pest groups real or remove the requirement**
   - Either enforce/document groups via Arch/static checks and backfill them, or update docs to avoid a non-enforced standard.

### P2 — Quality expansion

1. **INF-56-P2-A: Add contract tests for form state/API/export boundaries**
   - Match booking form shape.
   - Future JSON/API/import/export structures.
   - Queue/job payloads if introduced.

2. **INF-56-P2-B: Add snapshot/approval tests for generated output**
   - Match cards/event rundowns.
   - Title championship summaries.
   - Emails/exports when available.

3. **INF-56-P2-C: Add accessibility and visual coverage**
   - Login/dashboard.
   - Roster/event/title pages.
   - Critical forms/modals/tables.
   - Responsive navigation.

4. **INF-56-P2-D: Add static analysis for tests**
   - Consider a separate PHPStan config for tests at a pragmatic level.
   - Add checks for forbidden generic exceptions in business tests where domain exceptions exist.

5. **INF-56-P2-E: Normalize naming/casing**
   - `tests/Unit/database` → `tests/Unit/Database` or decide factory tests belong in Integration.
   - Remove redundant `Unit` suffix from files under `tests/Unit`.
   - Split lifecycle catch-all tests when they obscure 1:1 class mapping.

## PR Verification Matrix

Use this as the expected matrix for future Ringside PRs. Select the relevant row(s), but P0/P1 domain PRs should run at least the baseline.

| Change type | Required verification |
|---|---|
| Any PHP change | `composer test:lint`, `composer test:types`, relevant Pest suite/file |
| Architecture/test organization | Arch tests, mirror-pairing test, `composer test:types`, affected suite discovery |
| Pure domain logic / enum / value object / DTO | Targeted Unit tests, PHPStan, type coverage |
| Eloquent model/relationship/status change | Unit structural tests if pure; Integration DB tests for relationships/status; relevant factories |
| Action/service lifecycle change | Mirrored Integration action/service test; relevant workflow Feature test for user-visible behavior |
| Livewire table/modal/form change | Integration Livewire component test; Feature workflow if user journey changes; Browser test if JS/browser behavior matters |
| Controller/route/auth change | Mirrored Feature controller test; authorization test; navigation workflow if menu/page access changes |
| Match/event/title booking change | Integration action/service tests; Feature event booking workflow; Browser dynamic UI flow; performance query guard if list/selectors are touched |
| Browser/JS/Tailwind/UI change | Browser/E2E for critical flow; accessibility/visual check where available; `npm run build`; frontend lint/format if JS changed |
| Performance-sensitive query/list/report | Targeted performance/query-count test; integration test at realistic data volume |
| Export/email/generated summary | Contract test for data shape; snapshot/approval test for generated output; focused assertions for critical values |
| Release candidate | Full CI: Feature + Integration + Unit + Architecture + Static Analysis + Browser smoke + frontend build |

## Suggested Target Architecture

```text
tests/
  Architecture/
    ClassMirroringTest.php
    DomainBoundariesTest.php
    LaravelConventionsTest.php
  Unit/
    Data/...
    Enums/...
    Exceptions/...
    Support/DateHelperTest.php
    ValueObjects/HeightTest.php
    Rules/...              # only if no DB/framework dependency
  Integration/
    Actions/...
    Services/...
    Models/...             # relationships/pivots/computed DB behavior
    Livewire/...
    Database/Factories/...
  Feature/
    Http/Controllers/...
    Authorization/...
    Workflows/Promoter/... # high-level promoter jobs
  Browser/
    Auth/LoginTest.php
    Events/BookMatchTest.php
    Roster/ManageWrestlerTest.php
  Contract/
    MatchBookingFormStateTest.php
  Snapshot/
    EventCardSnapshotTest.php
  Performance/
    RosterTablesQueryBudgetTest.php
  Accessibility/
    CriticalPagesA11yTest.php
```

## Immediate Next Action

First finish the Laravel 13 baseline follow-ups from INF-62: **INF-103**, **INF-104**, **INF-105**, and **INF-106**. Then start **P0-B** (Architecture cleanup + mirror enforcement) and **P0-D** (critical action parity). That sequence keeps the baseline truthful before adding stricter guardrails.

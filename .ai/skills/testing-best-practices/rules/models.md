# Model Contracts

These are Jeffrey's model and model-test conventions, subject to explicit project guidance. Read the actual model, relevant migrations, factory, callers, casts/concerns/observers and tests before recommending changes. Confirm installed versions, suite layout and database engines. Guidance updates do not authorize application renames, migration edits, permission changes or test deletion.

## 1. Name the Domain and Relationships

Use singular domain names such as `Post` or `Order`, without a `Model` suffix, within the existing project namespaces. Relationships describe their meaning and cardinality, such as `author`, `category`, `episodes` or `promotionMemberships`; inspect custom foreign keys, pivot tables and polymorphic mappings rather than guessing them from columns. These are project conventions informed by [Laravel's model conventions](https://laravel.com/framework/docs/13.x/eloquent#eloquent-model-conventions), not runtime naming restrictions. Preserve legitimate irregular words, explicit table mappings and existing database identity; do not rename tables or merged migrations merely to align a class name.

## 2. Keep Appropriate Model Responsibilities

Models own persistence, relationships, casts and appropriate single-record behavior. Keep multi-step workflows, external integrations and reusable query orchestration in the project's established application layers; cite actual responsibility and callers before recommending extraction. Do not ban all domain behavior, require one-use Actions/services, or impose a line-count limit. Preserve package-owned traits/contracts and legitimate local hooks; do not duplicate shared package logic in an application merely to satisfy a new layout.

Follow existing query conventions: Jeffrey's defaults use custom Builders, while explicitly established attribute scopes in TLA/Mouse28 remain valid. Keep KneadIt's QueryBuilder naming and domain folders and Ringside's domain Builder layout. Do not migrate every scope or change observer ownership as a guidance side effect. Preserve native attribute declarations such as `#[Fillable]`, `#[Hidden]`, `#[ObservedBy]` and Builder/factory attributes where supported and established; do not force older property syntax or another application's conventions.

## 3. Exercise Relationship and Ownership Behavior

Use real persisted records to establish the actual related and excluded identities, foreign-key ownership and applicable pivot values/uniqueness, polymorphic mappings, tenant/context scopes and soft-delete boundaries. Include purposeful unrelated/foreign contrast records where exclusion is part of the contract. A relation's return type or a foreign-key string alone does not prove behavior. Use canonical comparisons unless the query explicitly defines ordering, with deterministic out-of-order fixtures when ordering matters.

Cover soft deletion, restoration, permanent deletion and application-owned cascade/observer effects when those are supported contracts. Verify what survives as well as what is removed; do not invent deletion behavior, delete production data, or test the database engine's generic cascade implementation. Trace existing lifecycle owners and tenant architecture before choosing cases. HTTP authorization/binding tests supplement model ownership tests; neither substitutes for the other.

## 4. Verify Casts and Defaults Through Persistence

For meaningful enum, date/time, decimal/money, nullable and custom-cast contracts, write representative values, save and reload a fresh instance, then assert the domain value and relevant raw stored representation when that representation is part of the application contract. Cover applicable boundaries such as null, zero, precision, normalization, timezone or invalid input without imposing every boundary on every column. Expected values use independent known literals/units rather than the cast's own conversion algorithm. Preserve the schema's units and enum storage values and the installed framework's actual rejection behavior; a cast does not automatically establish a database constraint.

Inspect model and database defaults separately. A factory may intentionally override a default, so a factory-created record alone does not prove it. Choose fresh/persisted fixtures that actually reach the default under test. Freeze time through supported helpers when time matters. Do not merely assert the contents of `casts()` or a default-attribute array.

## 5. Protect Mass Assignment and Serialization

Review the installed-version mass-assignment declaration (`#[Fillable]`, existing `$fillable`/guarded configuration) alongside FormRequests, Actions and other write callers. Follow the approved privileged/system-managed field policy; flag contradictory allowlists or local enforcement explicitly rather than silently treating a legacy pattern as an exception. Prove privileged fields cannot be changed through the untrusted write path with otherwise valid input, relevant unchanged state and permitted controls. Keep trusted explicit assignment distinct from user-controlled payloads. Do not remove fillable fields or change permissions during a guidance update.

Test model mass-assignment behavior through the real guarded `fill`/`create`/`update` boundary as appropriate, not a factory, `forceFill` or `unguarded` path that intentionally bypasses it. Factories arrange data and may unguard construction. Keep endpoint coverage too: model tests alone do not prove that a controller avoids raw request payloads or honors authorization.

For a defined serialization contract, assert intended fields/values and absence of private fields using distinctive markers; exercise meaningful appended/accessor/custom-cast output where applicable. Hidden fields control serialization, not access permissions, and arbitrary exact key lists should not freeze incidental framework output. Public responses, views, mail or exports still require their own outward-boundary tests. Read [security](security.md) and [endpoint tests](endpoint-tests.md) for those boundaries.

## 6. Test Application Behavior With Real Records

Default to mirrored Integration tests for database/framework-backed model behavior, such as `app/Models/Orders/Order.php` → `tests/Integration/Models/Orders/OrderTest.php`. Preserve explicit project suite conventions; Unit remains for genuinely framework-free logic and Feature for HTTP wiring. Do not move existing tests or invent an endpoint merely to satisfy a suite label. Use meaningful model cases where application behavior/configuration needs protection, not placeholder files that only prove Eloquent can save a generic row.

Use real queries and minimal deterministic factories with explicit ownership, valid domain states and deliberate contrast fixtures. Do not mock the query builder/model to claim persistence or relationship coverage. Reload after the target write before asserting persisted state; make post-action reads visible rather than burying deep query/refresh chains in expectations. Keep AAA and related `->and()` conventions from [assertions](assertions.md), [naming](naming.md), [test data](test-data.md) and [isolation](isolation.md).

Reviewer inventories changed or relied-upon model contracts and maps them to named cases, keeping lower-layer and HTTP coverage distinct:

| Model/responsibility | Schema, relationship, cast/default or protected-field contract | Existing owner/caller | Suite/file and named cases | Gap, conflict or not applicable |
| --- | --- | --- | --- | --- |

Report the relevant sources, applicable project differences, actual checks/engines and unverified behavior separately. A declaration check, matching filename or coverage percentage is not proof of model behavior. Keep unrelated legacy findings separate and retain all required native-database and coverage gates.

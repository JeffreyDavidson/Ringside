# Core Business Capabilities

Core business capabilities that govern the wrestling promotion management system.

## Overview

Core capabilities define what each entity type can do within the wrestling promotion system.

## Injury Capability

### Injury Rules
**Rule**: Only individual people can be injured
- **Eligible**: Wrestlers, Referees, Managers
- **Not Eligible**: TagTeams, Stables, Titles
- **Rationale**: Injuries affect individual people, not groups or objects

## Suspension Capability

### Suspension Rules
**Rule**: Only entities that can perform actions can be suspended
- **Eligible**: Wrestlers, Referees, Managers, TagTeams
- **Not Eligible**: Stables, Titles
- **Rationale**: Suspension prevents participation in activities

## Retirement Capability

### Retirement Rules
**Rule**: All active entities can be retired
- **Eligible**: Wrestlers, Managers, Referees, TagTeams, Titles, Stables
- **Rationale**: Any entity can cease active participation

## Employment Capability

### Employment Rules
**Rule**: Only entities that can work can be employed
- **Eligible**: Wrestlers, Managers, Referees, TagTeams
- **Not Eligible**: Titles, Stables
- **Rationale**: Employment represents a working relationship

Employment periods remain the authoritative persisted state. Employable models expose
relationship-backed state facts, while `EmploymentStatusResolver` reads those facts (or
the equivalent builder projection) and maps them to the computed `EmploymentStatus`
presented through each model's `status` attribute. Status classification remains outside
the model's persistence relationships, and shared lifecycle readers reuse builder
projections when status is rendered from list queries.

## Pull Capability

### Pull Rules
**Rule**: Only titles can be pulled from circulation
- **Eligible**: Titles only
- **Not Eligible**: All other entities
- **Rationale**: Pulling is a title-specific business action

## Debut Capability

### Debut Rules
**Rule**: Only titles and stables can be debuted
- **Eligible**: Titles, Stables
- **Not Eligible**: Wrestlers, Managers, Referees, TagTeams
- **Rationale**: Debuts represent the first time a title is contested or a stable is formed

## Booking Capability

### Booking Rules
**Rule**: Only entities that can compete in matches can be booked
- **Eligible**: Wrestlers, TagTeams
- **Not Eligible**: Managers, Referees, Titles, Stables
- **Rationale**: Booking is for match competition, not management or officiating

## Tag Team Membership Capability

Wrestlers explicitly define current and historical tag team membership through the `currentTagTeam`, `previousTagTeam`, and `tagTeams` Eloquent relationships. Because Wrestler is the only tag team member type, these persistence mappings belong directly on that model instead of behind a generic contract or concern. Whether a wrestler currently belongs to a tag team is determined by querying `currentTagTeam`; eligibility to join remains outside the model in validation rules and lifecycle collaborators.

Single current/previous tag team and current Stable lookups use Laravel's native `HasOneThrough` relationships through the persisted membership models. Wrestler and Tag Team define their own current and historical Stable relationships explicitly because their pivot models, tables, and foreign keys differ. Stable-joining eligibility remains in validation rules and lifecycle collaborators. Collection relationships remain `BelongsToMany` so callers can inspect complete history and membership pivot dates. Do not reintroduce the abandoned `ankurk91/laravel-eloquent-relationships` package.

### Single Current Tag Team

**Rule**: A wrestler belongs to at most one current tag team (a membership row with `left_at` null). Memberships ended with `left_at` are history and never conflict.

Enforcement has three layers:

- **Form rule (UX)**: `CanJoinTagTeam` rejects unavailable wrestlers at the Livewire form boundary so the user sees the error next to the field.
- **Action (authoritative)**: `EstablishMembershipAction` (create) and `SynchronizeMembershipAction` (update) call `LockIncomingWrestlersAction` before attaching. It locks the incoming wrestlers `ORDER BY id FOR UPDATE`, after the tag team row (inserted on create, locked by `refreshForUpdate()` on update), then `TagTeamMembershipEligibility` verifies none of them currently belongs to a different tag team (a soft-deleted team counts). A violation throws `TagTeams\CannotBeEstablishedException` and rolls the transaction back, so no tag team is created and no membership changes. Re-adding a wrestler to their own team is not a conflict. The tag-team form modal shows the exception message as a form error.
- **Database (backstop)**: the partial unique index `tag_teams_wrestlers_one_current_membership_unique` on `tag_teams_wrestlers (wrestler_id) WHERE left_at IS NULL` (a generated `current_wrestler_id` column plus unique index on MySQL/MariaDB), the same pattern as `stables_wrestlers_one_current_membership_unique`.

Lock order follows [Lifecycle Operation Boundaries](lifecycle-operation-boundaries.md): tag team before wrestlers, ascending id within the kind.

**Migration pre-flight**: `2026_10_01_162139_enforce_single_current_tag_team_membership_for_wrestlers` first looks for wrestlers with more than one current membership. It never edits data; if any exist it aborts before any schema change, naming each wrestler and the tag teams involved. Operators must end the extra memberships (set `left_at` on the `tag_teams_wrestlers` rows that should no longer be current) and re-run `php artisan migrate`.

Wrestler and Tag Team define their current and historical manager relationships directly so each model visibly owns its persistence mapping. The `Manageable` contract remains the type boundary for application code that operates on either model.

## User and Roster Separation

Application users authenticate and operate the promotion management system; they do not own wrestler or other roster records. User and roster models therefore have no direct Eloquent relationship or foreign key.

Global user accounts have `Unverified`, `Active`, and `Inactive` statuses.
Platform administrators can activate unverified accounts, deactivate active
accounts, and reactivate inactive accounts from the user directory. Account
status is separate from email verification: changing status does not set or
clear `email_verified_at`. Only active users are eligible for promotion
membership and authentication. New registrations remain unverified until a
platform administrator activates them. Inactive accounts cannot sign in, and
existing sessions are ended on their next web or Livewire request. Email
verification remains independent of account activation.

## Promotion Context and Membership

Users are global platform identities. A user's relationship to a promotion is
stored in the `promotion_user` membership table, where role and membership
status are scoped to that promotion. This allows one global user to participate
in more than one promotion without duplicating authentication records.

Promotion roles apply only within the active promotion context. Members can
view promotion-owned data. Managers can view and manage promotion-owned roster,
event, match, stable, and title data, but cannot update promotion settings or
membership roles. Owners have the manager capabilities and can also update
promotion settings and manage that promotion's memberships. Platform
administrators retain their global access, subject to the active-context
ownership guard. Promotion directory management, global users, and shared
venues remain outside promotion-member permissions.

The application resolves an active promotion through the scoped
`PromotionContextService`. Wrestlers, managers, referees, tag teams, stables,
events and titles now have nullable explicit promotion ownership. Venues are
global shared resources that can host events for multiple promotions. Venue
routes remain outside the promotion context middleware; a venue is globally
visible while its related event history is filtered by the active promotion.
When promotion context is enforced, new promotion-owned models receive the
active promotion during creation without exposing ownership columns to
mass-assignment.
Names are unique per promotion: wrestler and tag team `name` and
`signature_move`, stable, title and event `name` (validated in the create/edit
forms through `BaseForm::uniqueInPromotion()`, and in the restore eligibility
checks), so another promotion's values neither collide nor are revealed.
`exists` rules for promotion-owned records in those forms use
`BaseForm::existsInPromotion()`. Promotion slugs and venue names stay global.
At the database level, `stables_active_name_unique` is unique on
`(promotion_id, name) WHERE deleted_at IS NULL`; because NULLs are distinct in
unique indexes, a second filtered index
`stables_active_unowned_name_unique` keeps active unowned stable names unique
(SQLite and PostgreSQL; MySQL relies on form validation for unowned stables).
Existing unowned roster records can be assigned through the guarded
`promotions:backfill-roster-ownership` command; events and titles use
`promotions:backfill-event-title-ownership`. Match data inherits ownership
through its event. Promotion-scoped routes establish the context from the
session's selected active membership, defaulting to the first active
membership when none is selected. Promotion-owned model queries are then
filtered to that context, and platform administrators may operate without a
selected membership as a deliberate global-platform exception. The scope fails
closed: when no context is enforced, an authenticated non-administrator matches
no promotion-owned records (`PromotionContextService::failsClosed()`), while
administrators, console, queue and guest contexts stay unscoped. The dashboard
runs inside the `promotion.context` group, and users without an active
membership get a 403 "you are not a member of a promotion yet" page. Modals
authorize on mount (`create` on the model class, or `update` on the loaded
record), and `EstablishPromotionContext` runs before route model binding so
bindings resolve inside the promotion scope. Lifecycle and
history tables still require their own staged migrations, so this is not yet
fully isolated tenancy behavior.

Custom domains, subdomains, and physical tenant databases are deferred. The
initial boundary is a central platform with one database and explicit logical
promotion ownership.

## Related Documentation
- [Business Rules](business-rules.md)
- [Match System](match-system.md)
- [Championship System](championship-system.md)

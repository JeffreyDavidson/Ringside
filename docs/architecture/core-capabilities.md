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

### Tag Team Retirement and Unretirement

- **Retiring a tag team** retires the team and its eligible wrestlers and managers. The wrestlers stay current members of the retired team; only their other relationships (stable memberships, manager assignments, championship reigns) end.
- **A wrestler can come out of retirement alone.** If the wrestler is a current member of a retired tag team, unretiring them ends that membership on the unretire date, leaving them free to wrestle singles or join another team. A wrestler retired on their own (not through the team) leaves the team when retired, as before.
- **Unretiring a tag team** requires every wrestler who was on the team when it retired (membership open at the start of the current retirement: still open, or ended on or after that date because they came back alone) to be able to return with it. Each must not be soft-deleted, must not be a current member of another tag team and must not be injured. Retired wrestlers still on the team are unretired with it; a wrestler who came back alone rejoins with a new membership starting on the unretire date. If any cannot return, the team stays retired and the error names that wrestler. Fewer than two such wrestlers also blocks unretirement, along with the existing name-conflict rule.

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
- **Scheduled title debut**: A title whose debut is in the future is moved by changing its debut date (edit form), not by Reinstate; Reinstate is only for titles that were pulled

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
verification remains independent of account activation. The platform keeps at
least one active administrator: deactivating or demoting the last one is
rejected (`EnsureAnotherActiveAdministratorAction`). Every change that could
remove an administrator locks the active administrator rows in ascending id
order before the target user's row, so two administrators deactivating or
demoting each other at the same time queue instead of deadlocking. User emails are
case-insensitive: they are stored trimmed and lowercase, uniqueness is checked
ignoring case (including soft-deleted users) and enforced by a unique index on
`lower(email)`, and sign-in and password reset look users up ignoring case so
legacy rows stored with mixed case keep working. The migration refuses to run
(listing the user ids) when existing emails collide ignoring case; it never
rewrites stored emails.

## Promotion Context and Membership

Users are global platform identities. A user's relationship to a promotion is
stored in the `promotion_user` membership table, where role and membership
status are scoped to that promotion. This allows one global user to participate
in more than one promotion without duplicating authentication records.

A membership has a status: `Active` or `Suspended`. Only `Active` grants
anything. A pending invitation is not a membership: it is a `promotion_invitations`
row (`PromotionInvitation`) keyed by the invited email address, with the role the
owner chose, unique per promotion and email. Emails are stored trimmed and
lowercase (the model mutator, like `User::email`) and compared exactly after the
same normalisation. An owner invites by typing an email
(`Promotions\Members\Manage`, `InvitePromotionMemberAction`); no account is
needed, so nobody joins without consent and the form never reveals whether an
account exists. Unknown, inactive, unverified and active emails all store an
invitation and get one message that never names an account. The only answers
that differ are two things the owner can already see: the email already has a
pending invitation (`PromotionInvitationOutcome::AlreadyInvited`, the stored
invitation is kept as it is) or already belongs to a member of the promotion,
active or suspended (`AlreadyMember`). The action locks the promotion row, then
checks both. The owner's list shows a pending invitation by the typed email,
role and "Invitation pending", never by name; an owner can cancel it by
invitation id (scoped to the promotion). An invitation has no role or status to
change, so an owner cannot activate it behind anyone's back. The `AlreadyMember`
check includes soft-deleted accounts, which keep their `promotion_user` rows.
Inviting is rate limited to 30 attempts per promotion per hour (`RateLimiter`
key `promotion-invitations:{promotionId}`, in `Manage::addMember`); only
attempts that pass validation count, and the 31st shows an `email` error with the
minutes until the limit resets. The limit is per promotion, not per owner.

The member list is paginated at 25 per page (ordered by `created_at`, then
`user_id`); `memberRoles` entries are backfilled for the members on the rendered
page, and the role options are built once per render. Pending invitations are not
paginated. Accepting or declining an invitation for a promotion that does not
exist gets the same "no longer available" redirect as one without an invitation,
and `promotions/switch` answers 403 for a missing promotion and for one the user
does not belong to, so neither endpoint reveals which promotions exist.

An invitation expires 30 days after it is sent (`PromotionInvitation::EXPIRES_AFTER_DAYS`,
the `expires_at` column). Expiry is enforced on every read through the
`pending()` builder scope: `pendingInvitationsFor()` (switcher and no-membership
page), `AcceptPromotionInvitationAction` (an expired invitation is "no longer
available" and creates no membership) and the owner's list, which shows
"Expires {date}" in the promotion's time zone. The expiry moment itself counts as
expired. Inviting an email whose invitation has expired replaces it
(`InvitePromotionMemberAction` deletes the old row and saves a fresh one, so the
new role and a new 30 days apply); a still-pending one gives `AlreadyInvited`.
`PromotionInvitation` is `Prunable` and the scheduler runs `model:prune` for it
daily, which only deletes expired rows: nothing depends on it for correctness.

Whoever signs in with the invited email owns the invitation. There is no email
verification in the application, so administrator activation is the trust
anchor: only an `Active` account can sign in, and an administrator decides
which accounts become active. An invitation saved before the account existed is
shown once the account is registered, activated and signed in with that email;
this is the same trust model as password reset, which also hands control to
whoever holds the email address.
To make that decision informed, the administrator is shown what activation
unlocks: the users table's "Activate account" confirmation lists the account's
pending, unexpired invitations as "Promotion (Role)" pairs, and an administrator
who changes a user's email in the user form to an address with pending
invitations gets a non-blocking warning naming those promotions after saving
(the save is never blocked). Both read through
`PendingInvitationSummaryService`, which matches on the normalised email and
loads a whole page of users in one query. The invited user sees their invitations
(`PromotionContextService::pendingInvitationsFor()`, matched on the user's
normalised email, oldest first) as a section of the promotion switcher, on the
no-membership page when they have no active promotion and no access, or, for a
platform administrator who continues globally without a membership, as a block in
the sidebar itself (a compact envelope button when the sidebar is collapsed), with
Accept and Decline,
which post to `promotions.invitation.accept` / `.decline` (outside the promotion
context, because a user with no active membership must reach them).
`AcceptPromotionInvitationAction` locks the promotion and then the invitation
for the signed-in user's own email, creates an `Active` membership with the
invited role and deletes the invitation in one transaction. If the user already
has a membership of that promotion (for example a suspended one) it leaves the
membership untouched and deletes the now-useless invitation: an invitation can
never undo a suspension or change a role. `RemovePromotionInvitationAction` deletes the invitation for a
promotion and email (the owner cancelling or the user declining). Both find the
invitation through the signed-in user's email or the owner's promotion, never
through an id supplied by the invited user, and never touch memberships. The
middleware, the promotion switcher, `SwitchActivePromotionAction` and
`PromotionGate` read active memberships only, so an invitation gives no context
and no access until it is accepted. The last-owner guards count active owners
only.

A new invitation (`PromotionInvitationOutcome::Invited`, including one that replaces
an expired invitation) is announced by email: after its transaction commits,
`InvitePromotionMemberAction` sends `Mail\Promotions\PromotionInvitationMail`
(markdown, with a plain-text alternative) to the stored email, taking the inviting
user from the caller. `AlreadyInvited` and `AlreadyMember` send nothing. The email
names the inviter, promotion and role, shows the expiry date in the promotion's
time zone, links to the login and register pages and says the invitation is
accepted after signing in with that email address. It carries no token and reads
identically whether or not an account exists, so it reveals nothing about
accounts and grants nothing. It is sent synchronously (production has no queue
worker). A delivery failure is reported (`report()`) and swallowed: the
invitation stays saved and the outcome is still `Invited`, and the owner can
still see it in the application. Text lives in `lang/en/mail.php`.

Promotion roles apply only within the active promotion context. Members can
view promotion-owned data. Managers can view and manage promotion-owned roster,
event, match, stable, and title data, but cannot update promotion settings or
membership roles. Owners have the manager capabilities and can also update
promotion settings and manage that promotion's memberships. Every promotion
keeps at least one active owner: an owner counts only when both the membership
and the owner's user account are active (`EnsureAnotherActiveOwnerAction`).
Demoting or suspending the last such owner is rejected, and so is deactivating
(or marking unverified) a user account that is the last active owner of any
promotion (`Users\ChangeStatusAction`, `CannotRemoveLastOwnerException`, which
names the promotion). Platform
administrators retain their global access, subject to the active-context
ownership guard. Promotion directory management, global users, and shared
venues remain outside promotion-member permissions.

The application resolves an active promotion through the scoped
`PromotionContextService`. Wrestlers, managers, referees, tag teams, stables,
events and titles now have nullable explicit promotion ownership. Venues are
global shared resources that can host events for multiple promotions. Venue
routes, the promotions pages and user management run inside the
`promotion.context` group like the rest of the app, so a modal opened from them
cannot reach another promotion's records (Livewire only re-runs the context
middleware for routes that have it); only `promotions.switch` stays outside. A
venue is globally visible while its related event history is filtered by the
active promotion.

`PromotionGate::before()` runs on every Gate check, so membership is resolved
once per request: `PromotionContextService` memoises the user's active role per
user and promotion (seeded from the pivot row of the promotion selected by
`EstablishPromotionContext` or `SwitchActivePromotionAction`, otherwise read with
one query) and memoises the user's active promotions for the middleware and the
promotion switcher. `EstablishPromotionContext` resets the whole context
(`PromotionContextService::clear()`: promotion, enforcement and memo) when a
request starts, so nothing carries over from an earlier request that reused the
scoped instance (several requests in one test, or a long-lived worker). The
memo (the active promotions, one query, and the pending invitations for the
user's email, one more) is also dropped whenever the member Actions invite,
accept, remove or change a role or status
(`PromotionContextService::forgetMemberships()`). Any new code that writes
`promotion_user` or `promotion_invitations` must call it.
When promotion context is enforced, new promotion-owned models receive the
active promotion during creation without exposing ownership columns to
mass-assignment.
Names are unique per promotion: wrestler and tag team `name` and
`signature_move`, stable, title and event `name` (validated in the create/edit
forms through `BaseForm::uniqueInPromotion()`, and in the restore eligibility
checks), so another promotion's values neither collide nor are revealed.
`exists` rules for promotion-owned records in those forms use
`BaseForm::existsInPromotion()`. Both helpers scope to the record's own
promotion when editing (`BaseForm::$modelPromotionId`, locked) and, when
creating, to the promotion the creating hook will assign (the enforced
context), never to whatever context the request happens to have: a global
administrator without a membership has none, and comparing against
`promotion_id IS NULL` let edits pass duplicate names or reject a record's own
values. An administrator creating without a context creates unowned records,
so the rules then compare against unowned records. The match form scopes its
rules to the booked event's promotion instead (see
[Match System](match-system.md)). Promotion slugs and venue names stay global.
Tag team `name` and `signature_move` and title `name` are also guarded inside
their Actions, because the form rule alone cannot be race-free and no engine has
a unique index over them. `TagTeams\CreateAction`/`UpdateAction` and
`Titles\CreateAction`/`UpdateAction` first take `RecordNameLock`
(`app/Lifecycle/Naming`) inside their transaction, before the record's own row
lock: an upsert of a row of `record_name_locks` keyed by the sha256 of the
`GuardedName` kind, the promotion (a fixed marker for none) and the trimmed,
lower-cased, transliterated value, which holds an exclusive row lock until the
transaction ends and works the same on MySQL, PostgreSQL and SQLite. A tag team
locks its name, then its signature move (always in that order). The check that
follows enforces exactly the form rule's scope: another record of the same
promotion (or, without one, of no promotion) with the same value, soft-deleted
records included, ignoring the record itself on update. A taken value raises
`NameTakenException` (tag teams and titles each have one), which the form modal
shows on the name or signature move field. The Actions store and compare the
trimmed tag team name, so " The Kings" can no longer slip past the form rule,
which validates the raw value. A further guarded value (wrestler, event or venue
names) is one more `GuardedName` case.
At the database level, `stables_active_name_unique` is unique on
`(promotion_id, name) WHERE deleted_at IS NULL`; because NULLs are distinct in
unique indexes, a second filtered index
`stables_active_unowned_name_unique` keeps active unowned stable names unique
(SQLite and PostgreSQL; MySQL relies on form validation for unowned stables, plus the
`StableNameLock` that serializes creates, renames, restores and splits choosing the same unowned name).
Existing unowned roster records can be assigned through the guarded
`promotions:backfill-roster-ownership` command; events and titles use
`promotions:backfill-event-title-ownership`. Both include soft-deleted records so a restored record is not left unowned. Match data inherits ownership
through its event. Promotion-scoped routes establish the context from the
session's selected active membership, defaulting to the user's oldest active
membership (`promotion_user.created_at`, the lower promotion id breaking ties)
when none is selected or the selected one is no longer usable (suspended,
removed or deleted), so a promotion joined later never becomes the
default; the session is then
rewritten to the promotion actually used. Promotion-owned model queries are then
filtered to that context, and platform administrators may operate without a
selected membership as a deliberate global-platform exception. The scope fails
closed: when no context is enforced, an authenticated non-administrator matches
no promotion-owned records (`PromotionContextService::failsClosed()`), while
administrators, console, queue and guest contexts stay unscoped. The dashboard
runs inside the `promotion.context` group, and users without an active
membership get a 403 "you are not a member of a promotion yet" page. That page
uses the guest layout with a Log out button and no promotion navigation, because
every promotion link would lead back to it. Modals
authorize on mount (`create` on the model class, or `update` on the loaded
record), and `EstablishPromotionContext` runs before route model binding so
bindings resolve inside the promotion scope. Lifecycle and
history tables still require their own staged migrations, so this is not yet
fully isolated tenancy behavior.

Custom domains, subdomains, and physical tenant databases are deferred. The
initial boundary is a central platform with one database and explicit logical
promotion ownership.

## Promotion Time Zones and Event Dates

Each promotion has a `timezone` (an IANA identifier, `UTC` by default, so existing
promotions behave as before). It is edited on the promotion form and validated with
`Rule::in(timezone_identifiers_list())`.

`events.date` is always stored in UTC. People enter and read event dates in the
promotion's zone: the event form reads the `datetime-local` value as that zone and stores
the UTC instant (`Promotion::parseLocalTime()`), and shows the stored value back in it
(`Event::local_date`, backed by `Promotion::toLocalTime()`). For a new event the zone is the
enforced promotion context, the same promotion the creating hook assigns; an event with no
promotion uses the application time zone. The reschedule validation rule (`DateCanBeChanged`)
converts the entered value the same way, so it compares real instants.

Because the stored value is the true instant, comparisons such as the "event not held" gate
in `RecordResultAction` (`$event->date->isFuture()`) open at the event's local start time.
Event dates shown on the dashboard, events table and event page use `local_date`, and the
events table's date-range filter reads the chosen first and last day in the same zone. Venue
day booking is judged in the venue's own time zone, and the venue conflict message names that
venue-local date (for example "on Oct 6, 2026 (venue time)"), which can differ from the
promotion's day.

Changing a promotion's time zone does not rebase anything: stored event instants keep their
moment in time, so every existing event is shown at a different wall-clock time (and a late
event can land on another local day) in the new zone. The promotion form says so under the
time zone select when an existing promotion has events; nothing else is moved automatically.

Clock changes are handled at the form boundary. A wall-clock time that the zone skips when
clocks move forward (for example `2026-03-08T02:30` in America/New_York) is rejected by the
`LocalTimeExists` rule with a translated message instead of being silently shifted
(`Promotion::localTimeExists()`; `parseLocalTime()` itself still just parses). In the repeated
hour when clocks move back, a typed time reads as the first occurrence, but an edit that
leaves the date as prefilled keeps the event's stored instant (`Event::parseLocalDate()`), so
renaming an event in the second occurrence neither moves it an hour nor trips the
"already occurred" check.

Reign dates (`titles_championships.won_at` and `lost_at`) are also stored in UTC and shown in the
title's promotion zone through `TitleChampionship::local_won_at` and `local_lost_at`, so a 7 pm Los
Angeles title change shows that evening's date. Every list that prints them eager-loads
`title.promotion`.

## Lazy Loading

Lazy loading is prevented outside production (`Model::preventLazyLoading(! app()->isProduction())` in
`AppServiceProvider`): a relationship read that was not eager-loaded throws, in tests and local
development, instead of becoming a hidden N+1. Load relationships explicitly with `with()` or
`load()`; do not disable the guard.

## Related Documentation
- [Business Rules](business-rules.md)
- [Match System](match-system.md)
- [Championship System](championship-system.md)

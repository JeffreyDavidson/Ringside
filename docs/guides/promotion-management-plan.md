# Promotion Management Plan

Status: foundation implementation in progress. This document remains the
forward-looking product and migration plan; direct promotion ownership and
request-context enforcement are implemented, while lifecycle and history
ownership remain staged follow-up work.

## Product requirements

- A promotion represents a wrestling company with a name and slug. Promotion
  memberships store the scoped role and status; the current foundation keeps
  users global and does not duplicate authentication records.
- Users can switch between promotions where they have an active membership; the
  product may restrict promotion ownership to one promotion without limiting a
  user's ability to work for multiple promotions.
- Wrestlers, tag teams, managers, referees, stables, events, and titles belong
  to a promotion. Venues are global shared resources. Match data must follow
  its event's ownership boundary.
- Promotion data must remain isolated for reads and writes, including related
  records, direct record URLs, and background operations.
- Settings may include timezone, currency, date/time display, default match
  duration, and roster display preferences. Exact defaults remain to be decided.

## Acceptance criteria for future work

An authorized user can operate within one promotion, switch to another they
manage, and see only the selected promotion's records. A user cannot access or
associate records belonging to another promotion by changing submitted IDs.
Missing or unauthorized promotion context must not expose all records.

Existing data needs an explicit ownership/backfill plan before enforcing required
ownership. Tests must cover multiple users and promotions, reads, writes,
switching, cross-promotion relationships, and missing context.

The first ownership slice adds nullable `promotion_id` columns to wrestlers,
managers, referees, tag teams, and stables. The second adds the same explicit
ownership to events and titles; venues remain global shared resources, and match
data follows its event. Existing records can be previewed or assigned with
`promotions:backfill-roster-ownership` and
`promotions:backfill-event-title-ownership`; both commands require `--force`
before they change data. The `promotion.context` middleware establishes the
selected active membership for scoped routes, direct promotion-owned queries
are filtered to that context, and event matches inherit the event boundary.
Platform administrators remain a deliberate global exception when no active
membership is selected. Lifecycle and history ownership, promotion switching
UI, and background-job context remain follow-up work.

## Decisions to resolve before implementation

The historical proposal suggested ownership foreign keys, global scopes, a
session-based context, and Admin/Promoter account roles. These are design inputs,
not approved implementation instructions. Decide the authorization model,
background-job context, administrator access, deletion/retention rules, and data
migration behavior together. Do not copy the old cascading-delete or permissive
missing-context examples into production code.

Current [user and roster separation](../architecture/core-capabilities.md)
remains authoritative: accounts do not directly own roster entities. Review
existing role and account-status behavior before adopting the old user-system
checklist.

## Outside the initial proposal

Cross-promotion events, talent sharing, alliances, public directories, and
subscription/billing tiers remain separate future features.

See the [product roadmap](../product-roadmap.md) for planning ownership and
instructions for retrieving the original specs from Git.

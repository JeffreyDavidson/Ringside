# Promotion Management Plan

Status: retained product proposal; implementation and scheduling require a
separate task. This is not documentation of an existing tenancy boundary.

## Product requirements

- A promotion represents a wrestling company with a name, slug, owner, and
  settings. One account may manage multiple promotions.
- Users can create and switch between promotions they are authorized to manage.
- Wrestlers, tag teams, managers, referees, stables, events, venues, and titles
  belong to a promotion. Match data must follow its event's ownership boundary.
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

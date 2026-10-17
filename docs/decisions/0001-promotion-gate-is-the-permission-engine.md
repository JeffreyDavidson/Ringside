# 0001: PromotionGate is the permission engine

Status: accepted (2026-10-16)

## Context

Ringside is multi-promotion. A user holds one membership role (owner, manager or member) in a promotion, and that single role decides what they may do to everything the promotion owns: wrestlers, titles, events, matches, sponsors and crew contacts.

Laravel's usual shape is one policy per model, each answering "may this user do this to this record?". Here every one of those policies would ask the same question about the same membership and return the same answer for the same role.

## Decision

`App\Policies\PromotionGate`, registered as the application's `Gate::before` hook, is the permission engine.

- For promotion-owned models and class strings it reads the user's active membership of the current promotion through `PromotionContextService` and answers from `MembershipRole::allows()`. It also refuses records that belong to another promotion.
- Two small tables in the gate hold the subject-specific rules: sponsors and crew contacts are closed to members entirely (`MANAGER_ONLY_SUBJECTS`), and administrators may only create a crew contact while a promotion is current (`PROMOTION_BOUND_CREATES`).
- Administrators are allowed everything except another promotion's models while a promotion is enforced.
- The model policies are stubs. They exist so Laravel can resolve a policy for each model and so route `can:` middleware has a policy to fall through to, but they deny everything. Only `StorylinePolicy` holds real rules (stricter read access than ordinary promotion content), and the gate deliberately returns `null` for storylines so it can decide.

## Why

- Membership roles are tenant-wide. Changing what a manager may do is one edit to `MembershipRole`, applied to every model at once. With per-model policies the same change is a dozen edits that can drift apart.
- One place enforces the promotion boundary (cross-promotion records are refused). A missed `owns()` check in one of many policies would be a tenant leak.
- Membership is looked up once per request (memoised in `PromotionContextService`), which matters because the gate runs per table row.

## Trade-off

Laravel's own guidance, and the project owner's global convention, is that `Gate::before` is for a genuine super-admin bypass and that straightforward model permissions belong in the model's policy. Ringside deliberately breaks that for the reasons above. The cost:

- A reader who opens `WrestlerPolicy` finds only denials and has to know to look in `PromotionGate`. The stub docblocks and `.ai/rules/policies.md` say so.
- Per-model rules that genuinely differ (sponsors, crew contacts, storylines) need a table entry or a real policy instead of being obvious from the model's policy file.
- Any new promotion-owned model is covered automatically by `BelongsToPromotion`. A new ability must be added to `MembershipRole` or it is denied.

## Consequences

- This is an intentional exception to the global "Gate::before for super-admin bypass only" rule, not a violation to fix. Reviews should not ask for the gate to be dissolved into policies.
- New permission rules go in `MembershipRole` or the gate's subject tables, with a case in `tests/Integration/Policies/PromotionGateTest.php` for each role (owner, manager, member, suspended member, administrator, no membership).
- Revisit this decision if a model needs rules that vary by something other than the user's promotion role.

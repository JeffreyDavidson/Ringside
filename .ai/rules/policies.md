---
paths:
  - 'app/Policies/**'
---

# Policies

## Use Laravel policy method signatures
Use Laravel's conventional viewAny(User) and create(User) signatures for class-level abilities. Every instance-level ability must accept the authenticated User followed by the required concrete model; authorize those abilities with a model instance, never a model class.

## Read promotion membership through PromotionContextService
Gate checks run per table row, so never query `promotion_user` from a policy or the promotion gate; use `PromotionContextService::membershipRole()`, which is memoised per request. Code that writes `promotion_user` or `promotion_invitations` must call `forgetMemberships()` afterwards.

## PromotionGate is the permission engine
`PromotionGate` (Gate::before) decides every ability on promotion-owned models from the user's membership role, so the model policies are stubs that deny whatever the gate leaves alone. This is a deliberate Ringside exception to the general rule that Gate::before is only for a super-admin bypass; do not dissolve the gate into per-model policies. Put new role rules in `MembershipRole` or the gate's subject tables and prove them in `tests/Integration/Policies/PromotionGateTest.php` for every role (owner, manager, member, suspended member, administrator, no membership). See docs/decisions/0001-promotion-gate-is-the-permission-engine.md.

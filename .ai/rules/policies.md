---
paths:
  - 'app/Policies/**'
---

# Policies

## Use Laravel policy method signatures
Use Laravel's conventional viewAny(User) and create(User) signatures for class-level abilities. Every instance-level ability must accept the authenticated User followed by the required concrete model; authorize those abilities with a model instance, never a model class.

## Read promotion membership through PromotionContextService
Gate checks run per table row, so never query `promotion_user` from a policy or the promotion gate; use `PromotionContextService::membershipRole()`, which is memoised per request. Code that writes `promotion_user` must call `forgetMemberships()` afterwards.

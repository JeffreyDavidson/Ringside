---
paths:
  - 'app/Policies/**'
---

# Policies

## Use Laravel policy method signatures
Use Laravel's conventional viewAny(User) and create(User) signatures for class-level abilities. Every instance-level ability must accept the authenticated User followed by the required concrete model; authorize those abilities with a model instance, never a model class.

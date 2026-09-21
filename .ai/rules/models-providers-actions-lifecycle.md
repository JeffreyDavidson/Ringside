---
paths:
  - 'app/{Models,Providers,Actions,Lifecycle}/**'
---

# Models Providers Actions Lifecycle

## Enforce stable Eloquent morph aliases
All polymorphic persistence must use the enforced morph map and obtain aliases through getMorphClass(). Supported aliases are event, manager, match, referee, stable, tag_team, title, venue, and wrestler; never store PHP class names or ad hoc morph strings.

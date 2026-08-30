---
paths:
  - 'app/Models/{Concerns,Contracts}/**'
---

# Concerns Contracts

## Prefer relationship facts over inactive predicates
Keep lifecycle concerns focused on typed relationships and positive state facts. Do not add convenience predicates that only negate another relationship check; callers should use the relationship query directly.

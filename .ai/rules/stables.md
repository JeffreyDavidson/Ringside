---
paths:
  - 'app/{Actions/Stables/**,Lifecycle/Stable*Eligibility.php}'
---

# Stables

## Keep stable lifecycle eligibility outside models
Put stable activity, retirement, deletion/restoration, and restructuring eligibility in focused App\Lifecycle collaborators. Stable Actions must reload and lock participating stable rows inside their transaction before invoking throwing eligibility guards; Eloquent models expose persistence-derived state and relationships only.

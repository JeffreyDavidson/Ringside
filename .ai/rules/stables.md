---
paths:
  - 'app/{Actions/Stables/**,Lifecycle/Stable*Eligibility.php}'
---

# Stables

## Keep stable lifecycle eligibility outside models
Put stable activity, retirement, deletion/restoration, and restructuring eligibility in focused App\Lifecycle collaborators. Stable Actions must reload and lock participating stable rows inside their transaction before invoking throwing eligibility guards; Eloquent models expose persistence-derived state and relationships only.

## Keep former-member eligibility outside Stable
Keep former-member availability rules in StableFormerMemberEligibility. Reunion and unretirement eligibility may consume that collaborator; do not add business-eligibility query methods or concerns to the Stable model.

## Derive Stable status from lifecycle periods
Treat activity and retirement periods as the authoritative Stable state. Expose StableStatus only through the computed status accessor; do not persist or update a status column, and use the canonical lifecycle predicates instead of adding status aliases to Stable.

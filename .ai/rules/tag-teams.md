---
paths:
  - 'app/{Actions,Lifecycle}/TagTeams/**'
---

# Tag Teams

## Keep tag-team employment transitions explicit
Place tag-team employment and release eligibility in TagTeamEmploymentEligibility, not on the Eloquent model. Actions must lock and validate inside their transaction, and employment must reject existing current or future employment. Employing never implicitly un-retires a tag team, and releasing ends current partnerships; re-employment requires a newly established current membership.

## Keep lifecycle eligibility off the model
Place tag-team suspension, retirement, and deletion transition rules in their corresponding TagTeam*Eligibility collaborators. Predicates and throwing guards must remain aligned. Typed Actions reload and lock the tag team, validate inside the transaction, and retain persistence, dates, relationship cleanup, and member cascades.

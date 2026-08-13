---
paths:
  - 'app/{Actions,Lifecycle}/TagTeams/**'
---

# Tag Teams

## Keep tag-team employment transitions explicit
Place tag-team employment and release eligibility in TagTeamEmploymentEligibility, not on the Eloquent model. Actions must lock and validate inside their transaction, and employment must reject existing current or future employment. Employing never implicitly un-retires a tag team, and releasing ends current partnerships; re-employment requires a newly established current membership.

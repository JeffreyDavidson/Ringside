---
paths:
  - 'app/{Actions,Lifecycle}/**'
---

# Actions Lifecycle

## Centralize championship reign writes
Use ChampionshipReignManager as the single persistence boundary for opening, closing, and reconciling title championship reigns. Match and title Actions retain eligibility, locking, and workflow orchestration; TitleChampionshipQuery remains read-only reporting.

## Lock cascade members in ascending id order
Cascades over collections lock rows in ascending primary key order: order any collection you iterate before locking its members, and never lock a member row before the parent row that owns the cascade.

## End open membership rows through OpenPeriodEnder
End open stable, tag team, and manager assignment pivot rows with OpenPeriodEnder::end(): started rows end on the effective date and rows that start later are closed on their own start date, so left_at/fired_at never precedes joined_at/hired_at. Do not write the effective date directly onto every open row.

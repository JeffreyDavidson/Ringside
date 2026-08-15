---
paths:
  - 'app/Models/{Wrestlers,TagTeams}/**'
---

# Wrestlers Tag Teams

## Define distinct roster relationships directly
Define Wrestler and Tag Team manager assignments and stable memberships directly on each model so their distinct pivot models, tables, and keys remain visible. Retain Manageable and CanBeAStableMember as shared type boundaries; do not reintroduce configurable relationship traits for these mappings.

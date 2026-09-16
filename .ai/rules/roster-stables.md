---
paths:
  - 'app/{Actions/Stables/**,Services/Roster/Stables/**}'
---

# Roster Stables

## Keep stable membership writes in Actions
StableMembershipService is read-only and returns current membership data. Stable membership creation, removal, and synchronization are write operations owned by focused Actions; preserve membership history by dating ended pivots.

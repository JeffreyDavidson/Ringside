---
paths:
  - 'app/Actions/Matches/**'
---

# Actions Matches

## Apply championship lineage from match outcomes
RecordResultAction must apply match result metadata and all attached title outcomes in one transaction. Finishes that allow title changes transfer or establish each reign using the event date; DQ, countout, draws, and no-decisions retain the existing champion. Corrections may void reigns created by that match and restore the prior reign, but must reject rewriting lineage once a later reign depends on it.

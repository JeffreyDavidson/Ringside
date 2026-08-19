---
paths:
  - 'app/{Actions,Lifecycle,Exceptions}/Titles/**'
---

# Actions Lifecycle Exceptions Titles

## Validate title restoration name conflicts
Title restoration must lock the soft-deleted title inside its transaction and reject active title name conflicts before restoring. Preserve historical title records without bypassing active-name uniqueness.

---
paths:
  - 'app/Exceptions/**'
---

# Exceptions

## Use typed business failure reasons
Business exception messages are technical context and may change. When presentation must distinguish a failure, create the exception with self::forReason() and a stable BusinessRuleReason case; never derive application behavior by parsing getMessage(). General failures retain BusinessRuleReason::General.

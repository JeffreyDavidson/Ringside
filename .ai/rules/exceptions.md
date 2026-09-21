---
paths:
  - 'app/Exceptions/**'
---

# Exceptions

## Use typed business failure reasons
Business exception messages are technical context and may change. When presentation must distinguish a failure, create the exception with self::forReason() and a stable BusinessRuleReason case; never derive application behavior by parsing getMessage(). General failures retain BusinessRuleReason::General.

## Organize exceptions by owning domain
Place exceptions under the aggregate that owns the failed operation. Use a concern namespace such as Lifecycle or Scheduling only when the rule genuinely spans aggregates; do not use technical catch-all namespaces such as Data or BusinessRules.

## Keep exceptions as failure descriptions
Actions, validation concerns, and domain policies decide when a rule fails. Exception classes describe the failure and must not perform validation, query application state, or orchestrate behavior.

## Create exception factories from enforced rules
Add an exception factory only alongside an enforced caller. Do not pre-populate exception classes with hypothetical scenarios or future business rules.

## Keep exception documentation focused
Put workflow and business-context explanations in docs/architecture. Exception docblocks document only information PHP types cannot express; do not add usage examples or business essays.

## Use concrete final business exceptions
Concrete domain exceptions extend BaseBusinessException, are final, and use an Exception suffix.

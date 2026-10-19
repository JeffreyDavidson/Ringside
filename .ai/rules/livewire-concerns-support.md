---
paths:
  - 'app/Livewire/{Concerns,Support}/**'
---

# Livewire Concerns and Support

## Concerns are traits for behaviour several components share
A trait in `app/Livewire/Concerns` carries behaviour used by more than one component, such as `ExecutesBusinessActions` (run an Action, turn a `BaseBusinessException` into failure feedback) and `DispatchesActionFeedback`. Do not create one for a single component; keep that behaviour in the component. A concern never queries models directly or decides business rules.

## Support classes are plain helpers
A class in `app/Livewire/Support` is a plain object with no Livewire state, injected into a component (`BookableRosterSearch`, the roster route and error-message resolvers). It returns data for the component to render or return and keeps its dependencies in the constructor.

## Business failures use the shared path
Components catch only `BaseBusinessException` through `executeBusinessAction()` and show its message or a resolved one; they do not parse messages (see livewire.md and exceptions.md).

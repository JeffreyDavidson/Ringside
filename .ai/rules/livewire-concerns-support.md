---
paths:
  - 'app/Livewire/{Concerns,Support,Matches/Support}/**'
---

# Livewire Concerns and Support

## Concerns are traits for behaviour several components share
A trait in `app/Livewire/Concerns` carries behaviour used by more than one component, such as `ExecutesBusinessActions` (run an Action, turn a `BaseBusinessException` into failure feedback) and `DispatchesActionFeedback`. Do not create one for a single component; keep that behaviour in the component. A concern may look up the record a component was given an identifier for (`SearchesRosterMembers`, `ShowTableTrait`, `FiltersMatchHistory` do), but it does not decide business rules; eligibility and state decisions stay in Lifecycle classes and Actions.

## Support classes are plain helpers
A class in `app/Livewire/Support`, or in a domain's own `Support` folder (`app/Livewire/Matches/Support`), is a plain object with no Livewire state, injected into a component (`RosterMemberSearch`, `BookableRosterSearch`, the roster route and error-message resolvers). It returns data for the component to render or return and keeps its dependencies in the constructor.

## Business failures use the shared path
A component that runs an Action should do it through `executeBusinessAction()`, which catches `BaseBusinessException` (and its subclasses) and shows its message or a resolved one. A component with its own failure handling still catches only business exceptions and validation errors, never a bare `Exception`, and never parses messages (see livewire.md and exceptions.md).

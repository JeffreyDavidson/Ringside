---
paths:
  - 'app/Observers/**'
---

# Observers

## Observers protect a record's own invariants
An observer reacts to its own model's write and nothing else. The three observers here make audit records immutable (`LifecycleTransitionObserver`, `SponsorCommitmentRevisionObserver`, `TalentBookingRevisionObserver`): `updating` and `deleting` throw `LogicException`, because changing history is a programmer error, not a user-facing business refusal. Do not use an observer to write other records, send mail or dispatch work; that is the Action's job (see app.md on synchronous orchestration).

## Register with #[ObservedBy] and group by the model's domain
Attach the observer to its model with `#[ObservedBy]`, never in a service provider. Place it in the model's domain folder under `app/Observers` (`Events`, `Lifecycle`), and name it for the model with an `Observer` suffix.

## Test the guarded write through the model
There is no separate `tests/Integration/Observers` folder. Test each observer from its model's Integration test (`tests/Integration/Models/...`): the allowed insert succeeds, then an update and a delete are each refused with the stored row unchanged. Removing the observer must fail a test.

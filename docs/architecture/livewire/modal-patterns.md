# Livewire Modal Patterns

Ringside uses `wire-elements/modal` through class-based Livewire modal components.
Domain form modals extend `BaseFormModal`; specialized workflows may extend
`ModalComponent` directly when the create/edit lifecycle does not fit.

## Base modal responsibilities

`BaseModal` owns mechanics shared by model-backed modals:

- loads an existing model by identifier or initializes create state;
- assigns the model to its form;
- restores the original model state when clearing an edit form; and
- derives a create or edit title (see below).

It does not validate input or persist a model.

### Titles

`getModalTitle()` resolves the model from the form's locked `modelId` on every call
(no cached title property) and reads the attribute named by `$modelTitleField`
(`name` by default; Managers and Referees use `full_name`). The strings live in
`core.modal.edit` (`Edit :name`) and `core.modal.add` (`Add :model`). Modals that need
different wording, such as Stables, Titles, Venues, Events, Users, Matches, and Tag
Teams, override `getModalTitle()` and use `<domain>.modal.create` / `.edit` keys;
`ResultModal` uses `matches.modal.record_result` and `.correct_result`. Never
hard-code English titles in the modal.

## Base form modal responsibilities

`BaseFormModal` adds the shared form submission lifecycle:

- `submitForm()` (also reachable as `save()`) authorizes, calls `storeForm()`, and on
  success dispatches `refreshDatatable`, closes the modal, and dispatches the optional
  `$createdEventName` / `$updatedEventName` (only Promotions sets them, for a page reload); and
- the default `storeForm()` validates the form and calls `createForm()` or
  `updateForm()`, which throw `LogicException` unless the domain modal overrides them
  (or overrides `storeForm()` entirely, as the Matches modal does).

Concrete modals supply `getModelClass()` and the typed public `$form` property; each
component's `render()` method owns its Blade view.

## Domain modal pattern

A standard domain modal:

1. declares its typed form property;
2. receives create and update Actions through Livewire's `boot()` injection;
3. provides the model class used for edit-mode lookup;
4. validates and authorizes at the interaction boundary;
5. converts input with `toData()`; and
6. delegates persistence to the appropriate Action.

```php
final class FormModal extends BaseFormModal
{
    public CreateEditForm $form;

    private CreateAction $createAction;

    private UpdateAction $updateAction;

    public function boot(CreateAction $createAction, UpdateAction $updateAction): void
    {
        $this->createAction = $createAction;
        $this->updateAction = $updateAction;
    }

    protected function createForm(): void
    {
        $this->createAction->handle($this->form->toData());
    }

    protected function updateForm(): void
    {
        $this->updateAction->handle($this->form->manager(), $this->form->toData());
    }
}
```

Use method or `boot()` injection for Actions. Do not instantiate Actions manually
and do not move their mutation logic into the modal.

## Authorization

`BaseFormModal` calls `authorizeFormAccess()` both when the modal opens and when the
form is submitted: `Gate::authorize('create', $modelClass)` in create mode, or
`Gate::authorize('update', $model)` for the model resolved from the locked
`modelId`. A locked model identifier protects transport integrity but does not replace
authorization.

## Success and failure behavior

Return `false` from an overridden `storeForm()` only when the modal should remain open without a
successful completion event. On success, `BaseFormModal` refreshes tables, closes
and the modal.

Catch `BaseBusinessException` only when the interaction needs to translate a domain
failure into a user-facing message. Do not catch generic exceptions to hide
programmer or infrastructure failures.

## Specialized modal behavior

Domain modals may extend the shared lifecycle for behavior such as:

- loading option lists through computed properties;
- resetting dependent fields after another field changes;
- dispatching domain-specific success events; or
- generating development-only dummy data.

Keep those additions UI-focused. Reusable queries belong on Builders, and business
state transitions belong in Actions or lifecycle collaborators.

## Testing

- Assert authorization at modal entry and submission boundaries.
- Verify the modal chooses the correct create or update Action.
- Assert successful UI events and modal closure.
- Keep validation matrices with the form tests.
- Keep transaction and persistence assertions with Action integration tests.
- Use browser tests only for client-side modal behavior that component tests cannot
  prove.

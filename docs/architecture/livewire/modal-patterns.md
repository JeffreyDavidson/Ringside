# Livewire Modal Patterns

Ringside uses `wire-elements/modal` through class-based Livewire modal components.
Domain form modals extend `BaseFormModal`; specialized workflows may extend
`ModalComponent` directly when the create/edit lifecycle does not fit.

## Base modal responsibilities

`BaseModal` owns mechanics shared by model-backed modals:

- on `mount()`, authorizes `create` (no id) or `update` on the model found for the id, then
  loads that model into its form or resets the form for create;
- assigns the model to its form;
- restores the original model state when clearing an edit form; and
- derives a create or edit title (see below).

It does not validate input or persist a model.

### Titles

`getModalTitle()` resolves the model from the form's locked `modelId` on every call
(no cached title property) and reads the attribute named by `$modelTitleField`
(`name` by default; Managers, Referees and Users use `full_name`). The strings live in
`core.modal.edit` (`Edit :name`) and `core.modal.add` (`Add :model`). Modals that need
different wording (the Stables split, merge and reunite modals under `stables.modals.*.title`,
and the Matches form modal when editing and the result modal, under `matches.modal.*`) override `getModalTitle()` and
read a translation key. Never hard-code English titles in the modal.

## Base form modal responsibilities

`BaseFormModal` adds the shared form submission lifecycle:

- `submitForm()` (also reachable as `save()`) authorizes, calls `storeForm()`, and on
  success dispatches `refreshDatatable`, closes the modal, and dispatches the optional
  `$createdEventName` / `$updatedEventName` (only Promotions sets them, for a page reload); and
- the default `storeForm()` validates the form and calls `createForm()` or
  `updateForm()`, which throw `LogicException` unless the domain modal overrides them
  (or overrides `storeForm()` entirely, as the Matches modal does), inside
  `reportBusinessErrors()` (see below).

Concrete modals supply `getModelClass()` and the typed public `$form` property; each
component's `render()` method owns its Blade view.

## Domain modal pattern

A standard domain modal:

1. declares its typed form property;
2. receives create and update Actions through `boot()` injection, kept in private properties,
   because the base class calls `createForm()` and `updateForm()` itself and they cannot take
   the Actions as parameters;
3. provides the model class used for edit-mode lookup;
4. validates and authorizes at the interaction boundary;
5. converts input with `toData()`; and
6. delegates persistence to the appropriate Action.

```php
class FormModal extends BaseFormModal
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

Inject Actions; never resolve them with `app()` or `resolve()` (`LivewireArchitectureTest`
fails on it), never instantiate them manually, and do not move their mutation logic into the
modal. A modal action method that Livewire calls with client arguments takes the Action as a
method parameter instead (see the shared `laravel-conventions` rule `livewire.md`).

## Authorization

Authorization happens twice. `BaseModal::mount()` authorizes `create` or `update` when the
modal opens, and `BaseFormModal::authorizeFormAccess()` authorizes again on every submit:
`Gate::authorize('create', $modelClass)` in create mode, or `Gate::authorize('update', $model)`
for the model reloaded from the locked `modelId`. The submit-time check is deliberate: a
member who is downgraded or suspended after opening a form cannot save it. A locked model
identifier protects transport integrity but does not replace authorization.

## Success and failure behavior

`storeForm()` runs the save through `reportBusinessErrors()`. A `BaseBusinessException`
from the Action becomes an error on the form field named by `$businessErrorField` (or by an
overridden `businessErrorField($exception)` that can choose the field from the exception's
`reason()`), a `RecordChangedException` message gains "Your changes are still here." through
`ExplainsKeptInput` (both through the `ReportsBusinessErrors` concern) because the modal stays open with the input, and `storeForm()` returns `false` so no
completion event is dispatched. A modal that sets neither lets the exception propagate.
Validation failures throw and are shown by Livewire as usual.

Return `false` from an overridden `storeForm()` only when the modal should remain open without
a successful completion event. On success, `BaseFormModal` refreshes the tables and closes the
modal. Do not catch generic exceptions to hide programmer or infrastructure failures.

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

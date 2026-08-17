# Livewire Modal Patterns

Ringside uses `wire-elements/modal` through class-based Livewire modal components.
Domain form modals extend `BaseFormModal`; specialized workflows may extend
`ModalComponent` directly when the create/edit lifecycle does not fit.

## Base modal responsibilities

`BaseModal` owns mechanics shared by model-backed modals:

- loads an existing model by identifier or initializes create state;
- assigns the model to its form;
- restores the original model state when clearing an edit form; and
- derives a create or edit title.

It does not validate input or persist a model.

## Base form modal responsibilities

`BaseFormModal` adds the shared form submission lifecycle:

```php
abstract class BaseFormModal extends BaseModal
{
    abstract protected function getFormClass(): string;

    abstract protected function getModelClass(): string;

    abstract protected function getModalPath(): string;

    abstract protected function storeForm(): bool;

    public function submitForm(): bool
    {
        if ($this->model !== null) {
            $this->form->setModel($this->model);
        }

        if (! $this->storeForm()) {
            return false;
        }

        $this->dispatch('refreshDatatable');
        $this->closeModal();
        $this->dispatch('closeModal');
        $this->dispatch('form-submitted');

        return true;
    }
}
```

The three class metadata methods configure modal infrastructure. They are distinct
from the removed form-level model metadata: the modal genuinely needs the concrete
form class, model class, and Blade path to mount itself.

## Domain modal pattern

A standard domain modal:

1. declares its typed form property;
2. receives create and update Actions through Livewire's `boot()` injection;
3. provides the form, model, and view classes;
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

    protected function storeForm(): bool
    {
        $this->form->validate();

        if ($this->form->isEditing()) {
            $this->updateAction->handle($this->form->manager(), $this->form->toData());

            return true;
        }

        $this->createAction->handle($this->form->toData());

        return true;
    }
}
```

Use method or `boot()` injection for Actions. Do not instantiate Actions manually
and do not move their mutation logic into the modal.

## Authorization

Authorize immediately before a protected operation. For modals that expose create
and edit entry points, authorize the corresponding ability when opening or
submitting the modal. A locked model identifier protects transport integrity but
does not replace authorization.

```php
public function openModal(mixed $modelId = null): void
{
    if ($modelId === null) {
        Gate::authorize('create', EventMatch::class);
    } else {
        Gate::authorize('update', EventMatch::query()->findOrFail($modelId));
    }

    parent::openModal($modelId);
}
```

## Success and failure behavior

Return `false` from `storeForm()` only when the modal should remain open without a
successful completion event. On success, `BaseFormModal` refreshes tables, closes
the modal, and dispatches `form-submitted`.

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

## Matches exception

The Matches modal currently delegates `storeForm()` to the Matches form, whose
`store()` method assembles the complex competitor data and calls match Actions.
This is transitional. The target boundary is the same as other domains: the form
builds typed data and the modal calls the Actions.

## Testing

- Assert authorization at modal entry and submission boundaries.
- Verify the modal chooses the correct create or update Action.
- Assert successful UI events and modal closure.
- Keep validation matrices with the form tests.
- Keep transaction and persistence assertions with Action integration tests.
- Use browser tests only for client-side modal behavior that component tests cannot
  prove.

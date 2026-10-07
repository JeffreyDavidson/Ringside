# Modal Component Testing

## Overview

Domain modals extend `App\Livewire\Base\BaseFormModal` (which extends `BaseModal`, a
`LivewireUI\Modal\ModalComponent`). The base classes provide `openModal()`, `closeModal()`, `submitForm()`
(also `save()`), authorization, titles, and the success events. Domain modals add their Actions through
`boot()` and implement `getModelClass()`, `createForm()`, and `updateForm()`.

Modal tests live in `tests/Integration/Livewire/{Domain}/Modals/FormModalTest.php`. Shared lifecycle behavior is
covered once in `tests/Integration/Livewire/Base/BaseFormModalTest.php` and `BaseModalTest.php`; domain tests
cover what is specific to the domain.

## Lifecycle

```php
it('opens and closes the modal', function (): void {
    // Arrange
    $modal = livewire(FormModal::class)
        ->assertSet('isModalOpen', false);

    // Act
    $modal->call('openModal');

    // Assert
    $modal->assertSet('isModalOpen', true);
});
```

Pass a model id to enter edit mode: `livewire(FormModal::class, ['modelId' => $wrestler->id])` or
`$modal->call('openModal', $wrestler->id)`. Reopening with no id resets the form to create state.

## Titles

`BaseModal::getModalTitle()` builds the title from `core.modal.add` (`Add :model`, with the model name from
`core.models.*`) or `core.modal.edit` (`Edit :name`, from the field named by `$modelTitleField`). Assert the
rendered text:

```php
livewire(FormModal::class, ['modelId' => $wrestler->id])
    ->assertSee("Edit {$wrestler->name}");
```

Domain modals that override `getModalTitle()` use `<domain>.modal.*` keys; assert the text they render.
`tests/Integration/Livewire/Base/BaseModalTest.php` holds the cross-domain title datasets.

## Submission

Submit through the modal and assert persisted state and emitted events:

```php
$component = livewire(FormModal::class);

$component->set('form.name', 'Test Wrestler')
    ->set('form.hometown', 'Test City, TX')
    ->set('form.height_feet', 6)
    ->set('form.height_inches', 2)
    ->set('form.weight', 220)
    ->call('submitForm');

expect(Wrestler::where('name', 'Test Wrestler')->exists())->toBeTrue();
```

A successful submission dispatches `refreshDatatable` and `closeModal`, closes the modal
(`isModalOpen` is `false`), and dispatches the modal's optional `$createdEventName` / `$updatedEventName`
(only the Promotions modal sets them, as `promotion-saved`):

```php
$modal
    ->assertHasNoErrors()
    ->assertSet('isModalOpen', false)
    ->assertDispatched('refreshDatatable')
    ->assertDispatched('closeModal');
```

Source: `tests/Integration/Livewire/Base/BaseFormModalTest.php`.

Validation failures throw inside `storeForm()`, so the modal does not close and no events fire. Modals that
translate a `BaseBusinessException` into a field error (Events, Matches) return `false` from `storeForm()`;
assert the error with `assertHasErrors()` and that the record was not saved.

`createForm()` and `updateForm()` throw `LogicException` unless overridden. `StubFormModal`
(`tests/Integration/Livewire/Base/StubFormModal.php`) exists to prove that behavior.

## Authorization

`BaseFormModal` authorizes on `openModal()` and again on `submitForm()`: the `create` ability on the model class
in create mode, `update` on the model in edit mode. Assert with `assertForbidden()`:

```php
it('forbids users without administrative access from opening the venue form', function (string $actor) {
    if ($actor === 'basic user') {
        actingAs(basicUser());
    }

    $modal = livewire(FormModal::class);
    $modal->call('openModal');

    $modal->assertForbidden();
})->with([
    'guest' => ['guest'],
    'basic user' => ['basic user'],
]);
```

Source: `tests/Integration/Livewire/Venues/Modals/FormModalTest.php` (a file-level `beforeEach` there acts as an
administrator; the guest case skips `actingAs`).

## Rendering and dummy data

Assert the view with `assertViewIs('livewire.wrestlers.modals.form-modal')` and bindings with
`assertSeeHtml('wire:model="form.employment_date"')`.

Modals that implement `populateDummyData()` are exercised with `call('fillDummyFields')`. When the fill
depends on a random boolean, force the outcome with `forceFakerBoolean()` (`tests/Helpers/FakerHelpers.php`) and
drive it from a dataset; see `tests/Integration/Livewire/Titles/Modals/FormModalTest.php`.

## Related Documentation

- [Testing Guide](testing-guide.md)
- [Form Testing](testing-forms.md)
- [Modal Patterns](../../architecture/livewire/modal-patterns.md)
- [Component Architecture](../../architecture/livewire/component-architecture.md)

# Form Component Testing

## Overview

Forms extend `App\Livewire\Base\BaseForm`. They own input state, validation rules, edit hydration, and
conversion to a typed data object; they do not persist. Test each of those responsibilities. Persistence and
business rules belong to Action tests, and submission belongs to modal tests.

Form tests live in `tests/Integration/Livewire/{Domain}/Forms/CreateEditFormTest.php`.

## Constructing a form without a component

Forms can be instantiated directly. `Form` needs a component and a property name, so pass a `Double`
(`JMac\Testing\Double`) of `Livewire\Component`:

```php
use JMac\Testing\Double;
use Livewire\Component;

$form = new CreateEditForm(Double::for(Component::class), 'form');
```

## Typed data conversion

Assert that `toData()` maps input to the Action's data object, including blank optional fields:

```php
it('maps wrestler fields to typed application data', function (): void {
    // Arrange
    $form = new CreateEditForm(Double::for(Component::class), 'form');
    $form->name = 'Bret Hart';
    $form->hometown = 'Calgary, Alberta';
    $form->height_feet = 6;
    $form->height_inches = 0;
    $form->weight = 235;

    // Act
    $data = $form->toData();

    // Assert
    expect($data)->toBeInstanceOf(WrestlerData::class)
        ->and($data->height)->toEqual(new Height(6, 0))
        ->and($data->weight)->toEqual(new Weight(235));
});
```

Source: `tests/Integration/Livewire/Wrestlers/Forms/CreateEditFormTest.php`.

## Hydration

`setModel()` locks `modelId`, fills direct attributes, then calls the `loadModelData()` hook. Test the derived
values and the typed model lookup used by edit Actions:

```php
$form->setModel($wrestler);

expect($form->modelId)->toBe($wrestler->id)
    ->and($form->height_feet)->toBe(6)
    ->and($form->employment_date)->toBe('2024-01-15')
    ->and($form->wrestler()->is($wrestler))->toBeTrue();
```

Also cover `isCreating()` / `isEditing()` and the create-mode reset through `tests/Integration/Livewire/Base/BaseFormTest.php`
when changing `BaseForm` itself.

## Validation

Validation runs when the owning modal calls `$this->form->validate()`, so exercise rules through the modal
with `assertHasErrors()`. Errors are keyed with the `form.` prefix:

```php
livewire(FormModal::class)
    ->set('form.name', '')
    ->call('submitForm')
    ->assertHasErrors(['form.name' => 'required']);
```

Cover the rules that carry logic: uniqueness that ignores the record being edited, and domain rule objects
such as `CanChangeEmploymentDate` (see the "rejects changing an active wrestler employment date" test in
`tests/Integration/Livewire/Wrestlers/Modals/FormModalTest.php`). Custom attribute names from
`validationAttributes()` appear in the messages, so assert the message text when a label matters.

Keep validation matrices with the form or modal that owns the rules, and use datasets for repeated cases.

## Related Documentation

- [Testing Guide](testing-guide.md)
- [Modal Testing](testing-modals.md)
- [Form Patterns](../../architecture/livewire/form-patterns.md)
- [Component Architecture](../../architecture/livewire/component-architecture.md)

# PHPStan Generic Typing for Livewire Forms and Modals

The Livewire base classes are generic, so child classes get precise model and
form types without casts or `getAttribute()` calls.

## Base classes

`app/Livewire/Base/BaseForm.php`, `BaseModal.php`, and `BaseFormModal.php`
declare the templates:

- `BaseForm<TModel of Model>`
- `BaseModal<TModelForm of BaseForm, TModelType of Model>`
- `BaseFormModal<TForm of BaseForm, TModel of Model>`

Models are never stored as component state. Forms keep a locked `modelId` and
resolve the model when needed (see `.ai/rules/livewire.md`, "Resolve models from
locked identifiers").

## Concrete modals

Child modals bind the templates in an `@extends` tag and return the model class
from `getModelClass()`. Example: `app/Livewire/Referees/Modals/FormModal.php`.

```php
/**
 * @extends BaseFormModal<CreateEditForm, Referee>
 */
class FormModal extends BaseFormModal
{
    public CreateEditForm $form;

    protected function getModelClass(): string
    {
        return Referee::class;
    }
}
```

## Concrete forms

Forms extend `BaseForm` and expose a typed accessor that resolves the current
model from `modelId`, so callers get a real model type. Example:
`app/Livewire/Referees/Forms/CreateEditForm.php`.

```php
public function referee(): Referee
{
    return Referee::query()->findOrFail($this->modelId);
}
```

## Keys

Use `App\Support\ModelKey::of($model)` when a model key must be an `int|string`;
it throws a `LogicException` for a missing or non-scalar key instead of
scattering per-site guards.

## Verifying

```bash
composer test:types
```

This runs PHPStan for `app/` and for the Pest tests (`phpstan-pest.neon`).

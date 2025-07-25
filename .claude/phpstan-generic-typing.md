# PHPStan Generic Typing Best Practices

## Problem: Property Access on Dynamic Models

When working with Livewire components that handle Eloquent models, PHPStan often complains about property access:

```php
// ❌ PHPStan Error: Access to undefined property Model::$first_name
$this->model->first_name
```

This happens because `$this->model` is typed as the generic `Model` class, not the specific model (e.g., `User`, `Referee`).

## ❌ Wrong Solution: Using getAttribute()

```php
// This works but is verbose and defeats IDE autocomplete
'first_name' => $this->model->getAttribute('first_name'),
'last_name' => $this->model->getAttribute('last_name'),
```

## ✅ Correct Solution: Proper Generic Typing

### Step 1: Define Generic Templates in Base Classes

**BaseModal.php:**
```php
/**
 * @template TModelForm of \App\Livewire\Base\BaseForm
 * @template TModelType of Model
 */
abstract class BaseModal extends ModalComponent
{
    /**
     * @var TModelType|null
     */
    protected ?Model $model;
}
```

**BaseFormModal.php:**
```php
/**
 * @template TForm of BaseForm
 * @template TModel of Model
 *
 * @extends BaseModal<TForm, TModel>
 */
abstract class BaseFormModal extends BaseModal
{
    /**
     * @var TForm|null
     */
    public $form;
}
```

### Step 2: Specify Generic Types in Child Classes

```php
/**
 * @extends BaseFormModal<CreateEditForm, Referee>
 */
class FormModal extends BaseFormModal
{
    // Now $this->model is properly typed as Referee|null
    // Direct property access works!
    
    public function openModal(mixed $modelId = null): void
    {
        parent::openModal($modelId);
        
        if (isset($this->model) && ! is_null($this->model)) {
            $this->originalModelData = [
                'first_name' => $this->model->first_name,    // ✅ Works!
                'last_name' => $this->model->last_name,      // ✅ Works!
                'employment_date' => $this->model->firstEmployment?->started_at?->toDateString() ?? '',
            ];
        }
    }
}
```

### Step 3: Apply to Form Classes Too

```php
/**
 * @extends BaseForm<CreateEditForm, Referee>
 */
class CreateEditForm extends BaseForm
{
    // Form-specific properties and methods
}
```

## Benefits

1. **Type Safety**: PHPStan understands the exact model type
2. **IDE Support**: Full autocomplete and type checking in IDEs
3. **Cleaner Code**: Direct property access instead of `getAttribute()`
4. **Better Performance**: No method call overhead
5. **Maintainability**: Clear type relationships between components

## Results

- **Before**: 33 PHPStan errors with property access issues
- **After**: 5 PHPStan errors, all property access issues resolved
- **Improvement**: 85% error reduction + cleaner, more maintainable code

## Pattern Template

For any new Livewire component that works with models:

```php
/**
 * @extends BaseFormModal<YourFormClass, YourModelClass>
 */
class YourFormModal extends BaseFormModal
{
    protected function getFormClass(): string
    {
        return YourFormClass::class;
    }
    
    protected function getModelClass(): string  
    {
        return YourModelClass::class;
    }
    
    // Now $this->model is properly typed as YourModelClass|null
    // Direct property access works without PHPStan errors!
}
```

This approach scales to any generic class hierarchy and provides proper type safety throughout the application.
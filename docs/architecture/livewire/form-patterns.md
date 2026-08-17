# Livewire Form Patterns

Ringside uses Livewire form objects as typed input boundaries. A form owns public
input state, validation, edit-state hydration, and conversion to a domain data
object. It does not directly create or update Eloquent records.

## Responsibility boundary

| Layer | Responsibility |
| --- | --- |
| Form | Input state, validation rules, validation labels, edit hydration, and `toData()` conversion |
| Modal | Authorization, submission orchestration, Action resolution, and UI events |
| Action | Business rules, transactions, persistence, and relationship changes |
| Model | Eloquent state and relationships |

Public Livewire properties are untrusted input. Validate them before constructing
data objects or calling Actions. Model identifiers remain locked against client-side
changes and protected operations are authorized at the modal boundary.

## BaseForm

`App\Livewire\Base\BaseForm` provides only behavior shared by all create/edit forms:

- stores a locked model identifier;
- hydrates editable attributes through `setModel()`;
- distinguishes create and edit state;
- provides modal-title display values;
- invokes the optional `loadExtraData()` hook; and
- requires each form to define its validation rules.

It deliberately has no `store()`, `getModelClass()`, or `getModelData()` method.

```php
abstract class BaseForm extends Form
{
    protected ?Model $formModel = null;

    #[Locked]
    public int|string|null $modelId = null;

    public function setModel(?Model $formModel): void;

    public function isCreating(): bool;

    public function isEditing(): bool;

    protected function loadExtraData(): void;

    abstract protected function rules(): array;
}
```

## Domain forms

Each domain form extends `BaseForm` and normally contains:

1. typed public properties matching the editable fields;
2. `rules()` and, when useful, `validationAttributes()`;
3. `loadExtraData()` for state stored outside the model's direct attributes;
4. `toData()` to construct the Action's typed input; and
5. a typed model lookup used by edit Actions when required.

```php
final class CreateEditForm extends BaseForm
{
    public string $first_name = '';

    public string $last_name = '';

    public ?string $employment_date = null;

    public function toData(): ManagerData
    {
        return new ManagerData(
            first_name: $this->first_name,
            last_name: $this->last_name,
            employment_date: $this->employment_date === null
                ? null
                : Carbon::parse($this->employment_date),
        );
    }

    public function manager(): Manager
    {
        return Manager::query()->findOrFail($this->modelId);
    }

    protected function rules(): array
    {
        return [
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
        ];
    }
}
```

Do not expose generic model-class or model-data metadata merely to support a base
persistence method. The modal already knows which model and Actions it coordinates.

## Validation

Form validation is the entry boundary for request-shaped eligibility rules. Use
array rules and dedicated rule objects for reusable or domain-specific checks.
Actions may assume their data object was constructed from validated input, while
still enforcing transactional invariants that must hold for every caller.

```php
protected function rules(): array
{
    return [
        'employment_date' => [
            'nullable',
            'date',
            new CanChangeEmploymentDate($this->formModel),
        ],
    ];
}
```

## Editing and extra data

`BaseForm::setModel()` fills direct Eloquent attributes and then calls
`loadExtraData()`. Override that hook only for values derived from casts or related
records that cannot be filled from the model's attribute array.

```php
protected function loadExtraData(): void
{
    if (! $this->formModel instanceof Manager) {
        return;
    }

    $this->employment_date = $this->formModel
        ->firstEmployment?->started_at?->toDateString();
}
```

## Persistence

The owning modal validates the form, converts it to typed data, and calls the
appropriate create or update Action. This keeps transactions and business behavior
out of UI state objects.

```php
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
```

The Matches form is a temporary exception: its `store()` method currently resolves
the match Actions because match submission assembles a complex competitor payload.
That orchestration should move to the Matches modal without moving match invariants
out of the Actions.

## Testing

Test forms for validation, hydration, and data conversion. Test modals for Action
coordination and user-facing events. Test persistence and domain invariants through
the Action integration tests. Avoid reflection tests that freeze removed internal
methods instead of proving behavior.

# Livewire Component Examples

## Overview

These examples are trimmed from real files in `app/Livewire`, `resources/views/livewire`, and
`tests/Integration/Livewire`. The Wrestlers domain is the reference implementation. The structural
rules behind them live in [Livewire Standards](../../architecture/livewire-standards.md) and
[`docs/architecture/livewire/`](../../architecture/livewire/component-architecture.md); if an
example here drifts from the code, the code wins.

## Form Example

Source: `app/Livewire/Wrestlers/Forms/CreateEditForm.php`.

A form extends `App\Livewire\Base\BaseForm`, declares typed public properties, defines `rules()`,
hydrates derived values in the `loadModelData()` hook, and converts input to a typed data object with
`toData()`. It never persists anything.

```php
/** @extends BaseForm<Wrestler> */
class CreateEditForm extends BaseForm
{
    public string $name = '';

    public string $hometown = '';

    public int $height_feet = 0;

    public int $height_inches = 0;

    public int $weight = 0;

    public ?string $signature_move = '';

    public Carbon|string|null $employment_date = '';

    protected function loadModelData(Model $model): void
    {
        $this->employment_date = $model->firstEmployment?->started_at?->toDateString();

        $height = $model->height;
        $this->height_feet = (int) floor($height->toInches() / 12);
        $this->height_inches = $height->toInches() % 12;
    }

    public function toData(): WrestlerData
    {
        return new WrestlerData(
            name: $this->name,
            height: new Height($this->height_feet, $this->height_inches),
            weight: $this->weight,
            hometown: $this->hometown,
            signature_move: $this->signature_move ?: null,
            employment_date: $this->employment_date ? Carbon::parse($this->employment_date) : null,
        );
    }

    public function wrestler(): Wrestler
    {
        return Wrestler::query()->findOrFail($this->modelId);
    }

    protected function rules(): array
    {
        $wrestler = $this->isEditing() ? $this->wrestler() : null;

        return [
            'name' => ['required', 'string', 'max:255', Rule::unique('wrestlers', 'name')->ignore($this->modelId)],
            'employment_date' => ['nullable', 'date', new CanChangeEmploymentDate($wrestler)],
            // ...
        ];
    }
}
```

`BaseForm::setModel()` locks `modelId` and fills the direct attributes before calling
`loadModelData()`. The form resolves the current model from that locked identifier (`wrestler()`)
instead of keeping a model instance in component state.

## Modal Example

Source: `app/Livewire/Wrestlers/Modals/FormModal.php`.

A domain modal extends `App\Livewire\Base\BaseFormModal`, receives its create and update Actions in
`boot()`, and implements `getModelClass()`, `createForm()`, `updateForm()`, and `render()`.
`BaseFormModal` handles authorization, validation, `refreshDatatable`, and closing.

```php
/**
 * @extends BaseFormModal<CreateEditForm, Wrestler>
 */
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

    protected function getModelClass(): string
    {
        return Wrestler::class;
    }

    protected function updateForm(): void
    {
        $this->updateAction->handle($this->form->wrestler(), $this->form->toData());
    }

    protected function createForm(): void
    {
        $this->createAction->handle($this->form->toData());
    }

    public function render(): View
    {
        return view('livewire.wrestlers.modals.form-modal');
    }
}
```

The real class also implements `populateDummyData()` for development-only form filling.

The modal title comes from `BaseModal::getModalTitle()` (`core.modal.add` / `core.modal.edit`), so
the modal does not define one. Managers and Referees set `$modelTitleField = 'full_name'`.

### Translating a domain failure

Source: `app/Livewire/Events/Modals/FormModal.php`.

A modal that must show a business rule failure on a field overrides `storeForm()`, catches only
`BaseBusinessException`, and returns `false` so the modal stays open.

```php
#[\Override]
protected function storeForm(): bool
{
    try {
        return parent::storeForm();
    } catch (BaseBusinessException $exception) {
        $this->addError('form.venue_id', $exception->getMessage());

        return false;
    }
}
```

## Table Example

Source: `app/Livewire/Wrestlers/Tables/Main.php`.

Index tables extend `App\Livewire\Base\Tables\BaseTable` (which extends
`App\Livewire\Table\DataTableComponent`). They supply a `builder()`, `columns()`, and `filters()`
built from `App\Livewire\Table\Column`, `App\Livewire\Table\Filter`, and the filter classes in
`App\Livewire\Table\Filters`. `configure()` authorizes access.

```php
/** @extends BaseTable<Wrestler> */
class Main extends BaseTable
{
    use ExecutesBusinessActions;

    #[\Override]
    protected bool $showActionColumn = true;

    #[\Override]
    protected string $databaseTableName = 'wrestlers';

    #[\Override]
    protected string $routeBasePath = 'wrestlers';

    #[\Override]
    protected string $resourceName = 'wrestlers';

    /** @return WrestlerBuilder<Wrestler> */
    public function builder(): WrestlerBuilder
    {
        return Wrestler::query()
            ->withEmploymentStatusState()
            ->withFirstEmployment();
    }

    protected function configure(): void
    {
        Gate::authorize('viewAny', Wrestler::class);
    }

    public function columns(): array
    {
        return [
            Column::make(__('wrestlers.name'), 'name')
                ->searchable(),
            Column::make(__('core.status'), 'status')
                ->label(fn (Wrestler $row) => $row->status->label())
                ->excludeFromColumnSelect(),
            // ...
        ];
    }

    #[\Override]
    public function filters(): array
    {
        return [
            SelectFilter::make(__('core.status'))
                ->options(EmploymentStatus::filterOptions())
                ->filter(function (WrestlerBuilder $builder, string $value): void {
                    $status = EmploymentStatus::tryFrom($value);

                    if ($status !== null) {
                        $builder->whereEmploymentStatus($status);
                    }
                }),
        ];
    }

    public function delete(Wrestler $wrestler, DeleteAction $deleteAction): void
    {
        Gate::authorize('delete', $wrestler);

        $this->executeBusinessAction(function () use ($deleteAction, $wrestler): void {
            $deleteAction->handle($wrestler);
        }, __('wrestlers.actions.deleted'));
    }
}
```

`BaseTable` requires `getDefaultActionColumn()`. Each index table returns a column rendering a
`components.tables.columns.{entity}-actions` view built on `<x-tables.entity-actions>` (the shared
view/edit/remove entries on top of `<x-tables.row-actions-menu>`), which labels the trigger
`Actions for {name}` and gates every entry with `@can`. Pass `:removable="false"` to drop Remove and
add entity-specific entries through the slot. Status cells use `<x-tables.status :status="...">`,
which maps each status enum case to its dot colour. Set
`$showActionColumn = true` to append it after `columns()`.

Relationship history tables on detail pages (for example
`app/Livewire/Wrestlers/Tables/PreviousManagers.php`) extend a `BasePrevious*Table`, keep the
parent id in a `#[Locked]` public property, and authorize `view` on the parent in `configure()`.

## Actions Component Example

Source: `app/Livewire/Wrestlers/Components/Actions.php`.

Detail pages render `{Domain}\Components\Actions` for lifecycle transitions. Each transition resolves
its Action through method injection and runs it through `executeAuthorizedRosterAction()` from
`ExecutesRosterActions`, which authorizes with `Gate::authorize()`, translates a
`BaseBusinessException` into a failure message, and dispatches `wrestler-updated` plus a
`flash-message`. `canPerform()` decides which buttons render.

```php
class Actions extends Component
{
    use ChecksIndividualLifecycleEligibility;
    use ExecutesRosterActions;

    public Wrestler $wrestler;

    public function mount(Wrestler $wrestler): void
    {
        $this->wrestler = $wrestler;
    }

    public function employ(EmployAction $employAction): void
    {
        $this->executeAuthorizedRosterAction(
            RosterLifecycleAction::Employ,
            RosterEntityType::Wrestler,
            $this->wrestler,
            fn () => $employAction->handle($this->wrestler),
        );
    }

    public function canPerform(RosterLifecycleAction $action): bool
    {
        return Gate::allows($action->ability(), $this->wrestler)
            && $this->isEligibleFor($action, $this->wrestler);
    }
}
```

The repository formats each of these methods on a single line; they are wrapped here for
readability. The real component defines one method per lifecycle transition.

Managers, referees, tag teams, titles, and stables have equivalent components. Titles and stables use
`ExecutesBusinessActions` and their own `canPerform()` argument types; see
[Livewire Standards](../../architecture/livewire-standards.md#lifecycle-actions-components).

## Blade Examples

### Form modal view

Source: `resources/views/livewire/wrestlers/modals/form-modal.blade.php`.

The view is wrapped in `<x-form-modal>` and binds inputs to the form object with `wire:model="form.*"`.
Labels come from lang files.

```blade
<x-form-modal>
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2" data-test="wrestler-profile-grid">
        <x-form-modal.modal-input>
            <x-form.inputs.text :label="__('wrestlers.name')" wire:model="form.name" />
        </x-form-modal.modal-input>

        <x-form-modal.modal-input>
            <x-form.inputs.text :label="__('wrestlers.hometown')" wire:model="form.hometown" />
        </x-form-modal.modal-input>
    </div>
</x-form-modal>
```

### Actions view

Source: `resources/views/livewire/wrestlers/components/actions.blade.php`.

The view asks the component whether a button applies and never re-implements the eligibility rule.

```blade
@use('App\Enums\Roster\RosterLifecycleAction')

@if ($this->canPerform(RosterLifecycleAction::Employ))
    <x-buttons.success wire:click="employ">{{ __('core.lifecycle_actions.employ') }}</x-buttons.success>
@endif
```

## Testing Examples

Tests use Pest and the `livewire()` helper from `Pest\Livewire`. Integration tests mirror
`app/Livewire` under `tests/Integration/Livewire`. See the [Livewire Testing Guide](../../guides/livewire/testing-guide.md).

### Form test

Source: `tests/Integration/Livewire/Wrestlers/Forms/CreateEditFormTest.php`.

```php
use Livewire\Component;

it('maps blank optional fields to null', function (): void {
    // Arrange
    $form = new CreateEditForm(Double::for(Component::class), 'form');
    $form->name = 'Bret Hart';
    $form->hometown = 'Calgary, Alberta';
    $form->height_feet = 6;
    $form->height_inches = 0;
    $form->weight = 235;
    $form->signature_move = '';
    $form->employment_date = '';

    // Act
    $data = $form->toData();

    // Assert
    expect($data->signature_move)->toBeNull()
        ->and($data->employment_date)->toBeNull();
});
```

### Modal test

Source: `tests/Integration/Livewire/Wrestlers/Modals/FormModalTest.php`.

```php
beforeEach(function () {
    actingAs(administrator());
});

it('handles form validation errors', function () {
    $component = livewire(FormModal::class);

    $component->set('form.name', '')
        ->call('submitForm')
        ->assertHasErrors(['form.name' => 'required']);
});
```

### Table test

Source: `tests/Integration/Livewire/Wrestlers/Tables/MainTest.php`.

```php
it('filters wrestlers by name and clears the search', function (): void {
    // Arrange
    Wrestler::factory()->create(['name' => 'John Cena']);
    Wrestler::factory()->create(['name' => 'The Rock']);

    $component = livewire(Main::class);

    // Act
    $component->set('search', 'John');

    // Assert
    $component
        ->assertSee('John Cena')
        ->assertDontSee('The Rock');
});
```

### Actions test

Source: `tests/Integration/Livewire/Wrestlers/Components/ActionsTest.php`.

Action collaborators are replaced with `JMac\Testing\Double::for()` and bound in the container.

```php
$action->expects('handle')->with(
    Argument::satisfies(fn (mixed $actual): bool => $actual instanceof Wrestler && $actual->is($wrestler)),
);
app()->instance($actionClass, $action);

actingAs(administrator());
$component = livewire(Actions::class, ['wrestler' => $wrestler]);

$component->call($method);

$component
    ->assertDispatched('wrestler-updated')
    ->assertDispatched('flash-message', type: 'status', message: $message);
$action->verify();
```

## Related Documentation

- [Livewire Standards](../../architecture/livewire-standards.md)
- [Component Architecture](../../architecture/livewire/component-architecture.md)
- [Form Patterns](../../architecture/livewire/form-patterns.md)
- [Modal Patterns](../../architecture/livewire/modal-patterns.md)
- [Livewire Testing Guide](../../guides/livewire/testing-guide.md)

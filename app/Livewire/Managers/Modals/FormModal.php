<?php

declare(strict_types=1);

namespace App\Livewire\Managers\Modals;

use App\Actions\Managers\CreateAction;
use App\Actions\Managers\UpdateAction;
use App\Livewire\Base\BaseFormModal;
use App\Livewire\Concerns\GeneratesDummyData;
use App\Livewire\Managers\Forms\CreateEditForm;
use App\Models\Roster\Managers\Manager;
use Illuminate\View\View;

/**
 * @extends BaseFormModal<CreateEditForm, Manager>
 */
class FormModal extends BaseFormModal
{
    use GeneratesDummyData;

    public function mount(mixed $modelId = null): void
    {
        parent::mount($modelId);

        // Override title field to use full_name for managers
        $this->modelTitleField = 'full_name';
    }

    public CreateEditForm $form;

    private CreateAction $createAction;

    private UpdateAction $updateAction;

    public function boot(CreateAction $createAction, UpdateAction $updateAction): void
    {
        $this->createAction = $createAction;
        $this->updateAction = $updateAction;
    }

    protected function getFormClass(): string
    {
        return CreateEditForm::class;
    }

    protected function getModelClass(): string
    {
        return Manager::class;
    }

    protected function getModalPath(): string
    {
        return 'livewire.managers.modals.form-modal';
    }

    protected function getDummyDataFields(): array
    {
        return [
            'first_name' => fn () => fake()->firstName(),
            'last_name' => fn () => fake()->lastName(),
            'employment_date' => fn () => $this->generateOptionalEmploymentDate(),
        ];
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

    public function render(): View
    {
        return view($this->modalFormPath ?? 'livewire.managers.modals.form-modal');
    }
}

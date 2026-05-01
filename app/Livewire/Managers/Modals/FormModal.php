<?php

declare(strict_types=1);

namespace App\Livewire\Managers\Modals;

use App\Livewire\Base\BaseFormModal;
use App\Livewire\Concerns\GeneratesDummyData;
use App\Livewire\Managers\Forms\CreateEditForm;
use App\Models\Managers\Manager;
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
        $this->titleField = 'full_name';
    }

    public CreateEditForm $form;

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
            'start_date' => fn () => $this->generateOptionalStartDate(),
        ];
    }

    public function render(): View
    {
        return view($this->modalFormPath ?? 'livewire.managers.modals.form-modal');
    }
}

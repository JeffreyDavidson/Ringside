<?php

declare(strict_types=1);

namespace App\Livewire\Managers\Modals;

use App\Livewire\Base\BaseFormModal;
use App\Livewire\Managers\Forms\CreateEditForm;
use App\Models\Managers\Manager;

/**
 * @extends BaseFormModal<CreateEditForm, Manager>
 */
class FormModal extends BaseFormModal
{
    public function mount(mixed $modelId = null): void
    {
        parent::mount($modelId);
        
        // Override title field to use display_name for managers
        $this->modelTitleField = 'display_name';
        $this->titleField = 'display_name';
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
            'start_date' => fn () => fake()->optional(0.8)->dateTimeBetween('now', '+3 month')?->format('Y-m-d H:i:s'),
        ];
    }

    public function render(): \Illuminate\View\View
    {
        return view($this->modalFormPath ?? 'livewire.managers.modals.form-modal');
    }
}

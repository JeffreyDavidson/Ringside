<?php

declare(strict_types=1);

namespace App\Livewire\Referees\Modals;

use App\Livewire\Base\BaseFormModal;
use App\Livewire\Referees\Forms\CreateEditForm;
use App\Models\Referees\Referee;
use Illuminate\View\View;

/**
 * @extends BaseFormModal<CreateEditForm, Referee>
 */
class FormModal extends BaseFormModal
{
    /**
     * Store original model data for resetting purposes
     */
    public ?array $originalModelData = null;

    protected function getFormClass(): string
    {
        return CreateEditForm::class;
    }

    protected function getModelClass(): string
    {
        return Referee::class;
    }

    protected function getModalPath(): string
    {
        return 'livewire.referees.modals.form-modal';
    }

    protected function getDummyDataFields(): array
    {
        return [
            'first_name' => fn () => fake()->firstName(),
            'last_name' => fn () => fake()->lastName(),
            'employment_date' => fn () => ($date = fake()->optional(0.8)->dateTimeBetween('now', '+3 month')) ? $date->format('Y-m-d H:i:s') : null,
        ];
    }

    public function mount($modelId = null): void
    {
        parent::mount($modelId);

        // Set the title field to use full_name instead of name
        $this->modelTitleField = 'full_name';
        $this->titleField = 'full_name';
    }

    public function openModal(mixed $modelId = null): void
    {
        parent::openModal($modelId);

        // Store original model data if editing
        if (isset($this->model) && ! is_null($this->model)) {
            $this->originalModelData = [
                'first_name' => $this->model->first_name,
                'last_name' => $this->model->last_name,
                'employment_date' => $this->model->firstEmployment?->started_at?->toDateString() ?? '',
            ];
        } else {
            $this->originalModelData = null;
        }
    }

    public function clear(): void
    {
        if ($this->originalModelData) {
            // Reset to original model data when editing
            $this->form->first_name = $this->originalModelData['first_name'];
            $this->form->last_name = $this->originalModelData['last_name'];
            $this->form->employment_date = $this->originalModelData['employment_date'];
            $this->form->resetErrorBag();
            $this->form->resetValidation();
        } else {
            // Reset to empty state when creating - explicitly set defaults
            $this->form->first_name = '';
            $this->form->last_name = '';
            $this->form->employment_date = '';
            $this->form->resetErrorBag();
            $this->form->resetValidation();
        }
    }

    public function render(): View
    {
        return view($this->modalFormPath ?? 'livewire.referees.modals.form-modal');
    }
}

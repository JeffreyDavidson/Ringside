<?php

declare(strict_types=1);

namespace App\Livewire\Managers\Modals;

use App\Livewire\Base\BaseFormModal;
use App\Livewire\Managers\Forms\Form;
use App\Models\Managers\Manager;
use Illuminate\Support\Str;

/**
 * @extends BaseFormModal<Form, Manager>
 */
class FormModal extends BaseFormModal
{
    public Form $form;

    protected function getFormClass(): string
    {
        return Form::class;
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
            'first_name' => fn() => fake()->firstName(),
            'last_name' => fn() => fake()->lastName(),
            'start_date' => fn() => fake()->optional(0.8)->dateTimeBetween('now', '+3 month')?->format('Y-m-d H:i:s'),
        ];
    }

    public function render(): \Illuminate\View\View
    {
        return view($this->modalFormPath ?? 'livewire.managers.modals.form-modal');
    }
}

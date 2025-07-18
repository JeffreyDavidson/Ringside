<?php

declare(strict_types=1);

namespace App\Livewire\Referees\Modals;

use App\Livewire\Base\BaseFormModal;
use App\Livewire\Referees\Forms\CreateEditForm;
use App\Models\Referees\Referee;

/**
 * @extends BaseFormModal<CreateEditForm, Referee>
 */
class FormModal extends BaseFormModal
{

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
            'first_name' => fn() => fake()->firstName(),
            'last_name' => fn() => fake()->lastName(),
            'employment_date' => fn() => fake()->optional(0.8)->dateTimeBetween('now', '+3 month')?->format('Y-m-d H:i:s'),
        ];
    }
}

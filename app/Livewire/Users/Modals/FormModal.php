<?php

declare(strict_types=1);

namespace App\Livewire\Users\Modals;

use App\Livewire\Base\BaseForm;
use App\Livewire\Base\BaseFormModal;
use App\Livewire\Users\Forms\CreateEditForm;
use App\Models\Users\User;

/**
 * @extends BaseFormModal<CreateEditForm, User>
 */
class FormModal extends BaseFormModal
{
    public BaseForm $form;

    protected function getFormClass(): string
    {
        return CreateEditForm::class;
    }

    protected function getModelClass(): string
    {
        return User::class;
    }

    protected function getModalPath(): string
    {
        return 'livewire.users.modals.form-modal';
    }

    protected function getDummyDataFields(): array
    {
        return [
            'first_name' => fn() => fake()->firstName(),
            'last_name' => fn() => fake()->lastName(),
            'email' => fn() => fake()->unique()->safeEmail(),
        ];
    }
}

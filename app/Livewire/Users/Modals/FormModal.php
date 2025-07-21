<?php

declare(strict_types=1);

namespace App\Livewire\Users\Modals;

use App\Livewire\Base\BaseFormModal;
use App\Livewire\Users\Forms\CreateEditForm;
use App\Models\Users\User;
use Illuminate\Support\Facades\Gate;

/**
 * @extends BaseFormModal<CreateEditForm, User>
 */
class FormModal extends BaseFormModal
{

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
            'password' => fn() => 'password123',
            'password_confirmation' => fn() => 'password123',
            'role' => fn() => 'basic',
        ];
    }

    public function openModal(mixed $modelId = null): void
    {
        // Check authorization before opening modal
        if ($modelId !== null) {
            // Editing existing user - check update permission
            Gate::authorize('update', User::class);
        } else {
            // Creating new user - check create permission
            Gate::authorize('create', User::class);
        }

        parent::openModal($modelId);
    }

    public function getModalTitle(): string
    {
        if (isset($this->model)) {
            return 'Edit User';
        }

        return 'Create User';
    }

    public function submitForm(): bool
    {
        // Store whether we're creating or updating before the form submission
        $isCreating = $this->form->isCreating();
        
        $result = parent::submitForm();
        
        if ($result) {
            // Dispatch the appropriate event based on whether we created or updated
            if ($isCreating) {
                $this->dispatch('userCreated');
            } else {
                $this->dispatch('userUpdated');
            }
            
            // Reset the form after successful submission
            $this->form->reset();
        }
        
        return $result;
    }

    public function closeModal(): void
    {
        parent::closeModal();
        // Reset the form when modal is closed
        $this->form->reset();
    }

    public function render(): \Illuminate\View\View
    {
        return view($this->modalFormPath ?? 'livewire.users.modals.form-modal');
    }
}

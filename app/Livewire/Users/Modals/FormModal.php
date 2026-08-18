<?php

declare(strict_types=1);

namespace App\Livewire\Users\Modals;

use App\Actions\Users\CreateAction;
use App\Actions\Users\UpdateAction;
use App\Livewire\Base\BaseFormModal;
use App\Livewire\Users\Forms\CreateEditForm;
use App\Models\Users\User;
use Illuminate\View\View;

/**
 * @extends BaseFormModal<CreateEditForm, User>
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
        return User::class;
    }

    protected function getDummyDataFields(): array
    {
        return [
            'first_name' => fn () => fake()->firstName(),
            'last_name' => fn () => fake()->lastName(),
            'email' => fn () => fake()->unique()->safeEmail(),
            'password' => fn (): string => 'password123',
            'password_confirmation' => fn (): string => 'password123',
            'role' => fn (): string => 'basic',
        ];
    }

    public function getModalTitle(): string
    {
        if (isset($this->model)) {
            return 'Edit User';
        }

        return 'Create User';
    }

    protected function storeForm(): bool
    {
        $this->form->validate();

        if ($this->form->isEditing()) {
            $this->updateAction->handle($this->form->user(), $this->form->toData());

            return true;
        }

        $this->createAction->handle($this->form->toData());

        return true;
    }

    public function submitForm(): bool
    {
        $isCreating = $this->form->isCreating();

        $result = parent::submitForm();

        if ($result) {
            if ($isCreating) {
                $this->dispatch('userCreated');
            } else {
                $this->dispatch('userUpdated');
            }

            $this->form->reset();
        }

        return $result;
    }

    public function closeModal(): void
    {
        parent::closeModal();
        $this->form->reset();
    }

    public function render(): View
    {
        return view('livewire.users.modals.form-modal');
    }
}

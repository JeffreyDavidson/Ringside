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
    protected ?string $createdEventName = 'userCreated';

    protected ?string $updatedEventName = 'userUpdated';

    protected bool $resetFormAfterSubmission = true;

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

    protected function populateDummyData(): void
    {
        $this->form->first_name = fake()->firstName();
        $this->form->last_name = fake()->lastName();
        $this->form->email = fake()->unique()->safeEmail();
        $this->form->password = 'password123';
        $this->form->password_confirmation = 'password123';
        $this->form->role = 'basic';
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

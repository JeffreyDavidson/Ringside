<?php

declare(strict_types=1);

namespace App\Livewire\Users\Modals;

use App\Actions\Users\CreateAction;
use App\Actions\Users\UpdateAction;
use App\Exceptions\BaseBusinessException;
use App\Livewire\Base\BaseFormModal;
use App\Livewire\Users\Forms\CreateEditForm;
use App\Models\Users\User;
use Illuminate\View\View;

/**
 * @extends BaseFormModal<CreateEditForm, User>
 */
class FormModal extends BaseFormModal
{
    #[\Override]
    protected ?string $createdEventName = 'userCreated';

    #[\Override]
    protected ?string $updatedEventName = 'userUpdated';

    #[\Override]
    protected bool $resetFormAfterSubmission = true;

    #[\Override]
    protected string $modelTitleField = 'full_name';

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
        $this->form->password = 'password-12345';
        $this->form->password_confirmation = 'password-12345';
        $this->form->role = 'basic';
    }

    /**
     * AuthenticateSession stores the signed-in user's password hash in the session at the end of the request, so
     * when administrators edit their own account the guard must hold the updated user. Otherwise a changed password
     * would sign them out of this session too, not only their other sessions. Changing the password also re-issues
     * the "remember me" cookie, which would otherwise keep the old password hash, and signs out other devices.
     */
    protected function updateForm(): void
    {
        $updatedUser = $this->updateAction->handle($this->form->user(), $this->form->toData());

        if ($updatedUser->is(auth()->user())) {
            auth()->guard()->setUser($updatedUser);

            if ($this->form->password !== '') {
                auth()->guard()->logoutOtherDevices($this->form->password);
            }
        }
    }

    protected function createForm(): void
    {
        $this->createAction->handle($this->form->toData());
    }

    #[\Override]
    protected function storeForm(): bool
    {
        try {
            return parent::storeForm();
        } catch (BaseBusinessException $exception) {
            $this->addError('form.role', $exception->getMessage());

            return false;
        }
    }

    #[\Override]
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

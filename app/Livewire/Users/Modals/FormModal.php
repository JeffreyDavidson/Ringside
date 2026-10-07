<?php

declare(strict_types=1);

namespace App\Livewire\Users\Modals;

use App\Actions\Users\CreateAction;
use App\Actions\Users\UpdateAction;
use App\Exceptions\BaseBusinessException;
use App\Livewire\Base\BaseFormModal;
use App\Livewire\Concerns\DispatchesActionFeedback;
use App\Livewire\Users\Forms\CreateEditForm;
use App\Models\Promotions\PromotionInvitation;
use App\Models\Users\User;
use App\Services\Promotions\PendingInvitationSummaryService;
use Illuminate\View\View;

/**
 * @extends BaseFormModal<CreateEditForm, User>
 */
class FormModal extends BaseFormModal
{
    use DispatchesActionFeedback;

    #[\Override]
    protected bool $resetFormAfterSubmission = true;

    #[\Override]
    protected string $modelTitleField = 'full_name';

    public CreateEditForm $form;

    private CreateAction $createAction;

    private UpdateAction $updateAction;

    private PendingInvitationSummaryService $pendingInvitationSummaries;

    public function boot(CreateAction $createAction, UpdateAction $updateAction, PendingInvitationSummaryService $pendingInvitationSummaries): void
    {
        $this->createAction = $createAction;
        $this->updateAction = $updateAction;
        $this->pendingInvitationSummaries = $pendingInvitationSummaries;
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
        $user = $this->form->user();
        $previousEmail = $user->email;
        $updatedUser = $this->updateAction->handle($user, $this->form->toData());

        $this->warnAboutInvitationsReachedByEmailChange($updatedUser, $previousEmail);

        if ($updatedUser->is(auth()->user())) {
            auth()->guard()->setUser($updatedUser);

            if ($this->form->password !== '') {
                auth()->guard()->logoutOtherDevices($this->form->password);
            }
        }
    }

    /** Changing an email moves the account onto that address's invitations, so the administrator is told which. */
    private function warnAboutInvitationsReachedByEmailChange(User $user, string $previousEmail): void
    {
        if (PromotionInvitation::normalizeEmail($previousEmail) === PromotionInvitation::normalizeEmail($user->email)) {
            return;
        }

        $invitations = $this->pendingInvitationSummaries->forEmail($user->email);

        if ($invitations === null) {
            return;
        }

        $this->dispatchActionWarning(__('users.email_change_invitations_warning', [
            'name' => $user->full_name,
            'invitations' => $invitations,
        ]));
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

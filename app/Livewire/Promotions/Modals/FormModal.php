<?php

declare(strict_types=1);

namespace App\Livewire\Promotions\Modals;

use App\Actions\Promotions\CreateAction;
use App\Actions\Promotions\UpdateAction;
use App\Livewire\Base\BaseFormModal;
use App\Livewire\Promotions\Forms\CreateEditForm;
use App\Models\Promotions\Promotion;
use Illuminate\Support\Str;
use Illuminate\View\View;

/** @extends BaseFormModal<CreateEditForm, Promotion> */
class FormModal extends BaseFormModal
{
    #[\Override]
    protected ?string $createdEventName = 'promotion-saved';

    #[\Override]
    protected ?string $updatedEventName = 'promotion-saved';

    #[\Override]
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
        return Promotion::class;
    }

    protected function populateDummyData(): void
    {
        $this->form->name = fake()->company();
        $this->form->slug = Str::slug($this->form->name);
    }

    public function getModalTitle(): string
    {
        return $this->form->isEditing()
            ? __('promotions.edit_title')
            : __('promotions.create_title');
    }

    protected function updateForm(): void
    {
        $this->updateAction->handle($this->form->promotion(), $this->form->toData());
    }

    protected function createForm(): void
    {
        $this->createAction->handle($this->form->toData());
    }

    #[\Override]
    public function closeModal(): void
    {
        parent::closeModal();
        $this->form->reset();
    }

    public function render(): View
    {
        return view('livewire.promotions.modals.form-modal');
    }
}

<?php

declare(strict_types=1);

namespace App\Livewire\Managers\Modals;

use App\Actions\Managers\CreateAction;
use App\Actions\Managers\UpdateAction;
use App\Livewire\Base\BaseFormModal;
use App\Livewire\Managers\Forms\CreateEditForm;
use App\Models\Roster\Managers\Manager;
use Illuminate\View\View;

/**
 * @extends BaseFormModal<CreateEditForm, Manager>
 */
class FormModal extends BaseFormModal
{
    public function mount(int|string|null $modelId = null): void
    {
        parent::mount($modelId);

        $this->modelTitleField = 'full_name';
    }

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
        return Manager::class;
    }

    protected function populateDummyData(): void
    {
        $this->form->first_name = fake()->firstName();
        $this->form->last_name = fake()->lastName();
        $this->form->employment_date = $this->generateOptionalEmploymentDate();
    }

    protected function updateForm(): void
    {
        $this->updateAction->handle($this->form->manager(), $this->form->toData());
    }

    protected function createForm(): void
    {
        $this->createAction->handle($this->form->toData());
    }

    public function render(): View
    {
        return view('livewire.managers.modals.form-modal');
    }
}

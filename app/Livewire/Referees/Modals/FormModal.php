<?php

declare(strict_types=1);

namespace App\Livewire\Referees\Modals;

use App\Actions\Referees\CreateAction;
use App\Actions\Referees\UpdateAction;
use App\Livewire\Base\BaseFormModal;
use App\Livewire\Referees\Forms\CreateEditForm;
use App\Models\Roster\Referees\Referee;
use Illuminate\View\View;

/**
 * @extends BaseFormModal<CreateEditForm, Referee>
 */
class FormModal extends BaseFormModal
{
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
        return Referee::class;
    }

    protected function populateDummyData(): void
    {
        $this->form->first_name = fake()->firstName();
        $this->form->last_name = fake()->lastName();
        $this->form->employment_date = $this->generateOptionalEmploymentDate();
    }

    protected function updateForm(): void
    {
        $this->updateAction->handle($this->form->referee(), $this->form->toData());
    }

    protected function createForm(): void
    {
        $this->createAction->handle($this->form->toData());
    }

    public function render(): View
    {
        return view('livewire.referees.modals.form-modal');
    }
}

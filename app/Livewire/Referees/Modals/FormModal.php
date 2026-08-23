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
    /**
     * Store original model data for resetting purposes
     *
     * @var array{first_name: string, last_name: string, employment_date: string}|null
     */
    public ?array $originalModelData = null;

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

    public function mount(int|string|null $modelId = null): void
    {
        parent::mount($modelId);

        $this->modelTitleField = 'full_name';
    }

    public function openModal(int|string|null $modelId = null): void
    {
        parent::openModal($modelId);

        if (isset($this->model)) {
            $this->originalModelData = [
                'first_name' => $this->model->first_name,
                'last_name' => $this->model->last_name,
                'employment_date' => $this->model->firstEmployment?->started_at?->toDateString() ?? '',
            ];
        } else {
            $this->originalModelData = null;
        }
    }

    public function clear(): void
    {
        if ($this->originalModelData) {
            $this->form->first_name = $this->originalModelData['first_name'];
            $this->form->last_name = $this->originalModelData['last_name'];
            $this->form->employment_date = $this->originalModelData['employment_date'];
            $this->form->resetErrorBag();
            $this->form->resetValidation();
        } else {
            $this->form->first_name = '';
            $this->form->last_name = '';
            $this->form->employment_date = '';
            $this->form->resetErrorBag();
            $this->form->resetValidation();
        }
    }

    public function render(): View
    {
        return view('livewire.referees.modals.form-modal');
    }
}

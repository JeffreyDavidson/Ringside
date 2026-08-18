<?php

declare(strict_types=1);

namespace App\Livewire\Stables\Modals;

use App\Actions\Stables\CreateAction;
use App\Actions\Stables\UpdateAction;
use App\Livewire\Base\BaseFormModal;
use App\Livewire\Concerns\Data\PresentsManagersList;
use App\Livewire\Concerns\Data\PresentsTagTeamsList;
use App\Livewire\Concerns\Data\PresentsWrestlersList;
use App\Livewire\Stables\Forms\CreateEditForm;
use App\Models\Roster\Stables\Stable;
use Illuminate\Support\Str;
use Illuminate\View\View;

/**
 * @extends BaseFormModal<CreateEditForm, Stable>
 */
class FormModal extends BaseFormModal
{
    use PresentsManagersList;
    use PresentsTagTeamsList;
    use PresentsWrestlersList;

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
        return Stable::class;
    }

    protected function populateDummyData(): void
    {
        $this->form->name = Str::of(fake()->sentence(2))->title()->value();
        $this->form->started_at = $this->generateOptionalStartDate();
    }

    public function getModalTitle(): string
    {
        if (isset($this->model)) {
            return 'Edit Stable';
        }

        return 'Create Stable';
    }

    protected function storeForm(): bool
    {
        $this->form->validate();

        if (! $this->form->isCreating()) {
            $this->updateAction->handle($this->form->stable(), $this->form->toData());

            return true;
        }

        $this->createAction->handle($this->form->toData());

        return true;
    }

    public function render(): View
    {
        return view('livewire.stables.modals.form-modal');
    }
}

<?php

declare(strict_types=1);

namespace App\Livewire\TagTeams\Modals;

use App\Actions\TagTeams\CreateAction;
use App\Actions\TagTeams\UpdateAction;
use App\Livewire\Base\BaseFormModal;
use App\Livewire\Concerns\Data\PresentsManagersList;
use App\Livewire\Concerns\Data\PresentsWrestlersList;
use App\Livewire\TagTeams\Forms\CreateEditForm;
use App\Models\Roster\TagTeams\TagTeam;
use App\Models\Roster\Wrestlers\Wrestler;
use Illuminate\Support\Str;
use Illuminate\View\View;

/**
 * @extends BaseFormModal<CreateEditForm, TagTeam>
 */
class FormModal extends BaseFormModal
{
    use PresentsManagersList;
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
        return TagTeam::class;
    }

    protected function populateDummyData(): void
    {
        $wrestlers = Wrestler::query()
            ->inRandomOrder()
            ->limit(2)
            ->get(['id']);

        $this->form->name = Str::of(fake()->sentence(2))->title()->value();
        $this->form->signature_move = Str::of(fake()->optional(0.8)->sentence(3))->title()->value();
        $this->form->employment_date = $this->generateOptionalEmploymentDate();
        $this->form->wrestlerA = $wrestlers->get(0)?->id;
        $this->form->wrestlerB = $wrestlers->get(1)?->id;
    }

    protected function updateForm(): void
    {
        $this->updateAction->handle($this->form->tagTeam(), $this->form->toData());
    }

    protected function createForm(): void
    {
        $this->createAction->handle($this->form->toData());
    }

    public function render(): View
    {
        return view('livewire.tag-teams.modals.form-modal');
    }
}

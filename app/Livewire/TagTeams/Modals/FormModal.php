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

    protected function getDummyDataFields(): array
    {
        $wrestlerIds = Wrestler::query()
            ->inRandomOrder()
            ->limit(2)
            ->pluck('id');

        return [
            'name' => fn () => Str::of(fake()->sentence(2))->title()->value(),
            'signature_move' => fn () => Str::of(fake()->optional(0.8)->sentence(3))->title()->value(),
            'employment_date' => fn (): ?string => $this->generateOptionalEmploymentDate(),
            'wrestlerA' => fn () => $wrestlerIds->first(),
            'wrestlerB' => fn () => $wrestlerIds->get(1),
        ];
    }

    protected function storeForm(): bool
    {
        $this->form->validate();

        if (! $this->form->isCreating()) {
            $this->updateAction->handle($this->form->tagTeam(), $this->form->toData());

            return true;
        }

        $this->createAction->handle($this->form->toData());

        return true;
    }

    public function render(): View
    {
        return view('livewire.tag-teams.modals.form-modal');
    }
}

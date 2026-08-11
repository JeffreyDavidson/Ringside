<?php

declare(strict_types=1);

namespace App\Livewire\TagTeams\Modals;

use App\Actions\TagTeams\CreateAction;
use App\Actions\TagTeams\UpdateAction;
use App\Livewire\Base\BaseFormModal;
use App\Livewire\Concerns\Data\PresentsManagersList;
use App\Livewire\Concerns\Data\PresentsWrestlersList;
use App\Livewire\Concerns\GeneratesDummyData;
use App\Livewire\TagTeams\Forms\CreateEditForm;
use App\Models\TagTeams\TagTeam;
use App\Models\Wrestlers\Wrestler;
use Illuminate\Support\Str;
use Illuminate\View\View;

/**
 * @extends BaseFormModal<CreateEditForm, TagTeam>
 */
class FormModal extends BaseFormModal
{
    use GeneratesDummyData;
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

    protected function getFormClass(): string
    {
        return CreateEditForm::class;
    }

    protected function getModelClass(): string
    {
        return TagTeam::class;
    }

    protected function getModalPath(): string
    {
        return 'livewire.tag-teams.modals.form-modal';
    }

    protected function getDummyDataFields(): array
    {
        /** @var Wrestler $wrestlerA */
        /** @var Wrestler $wrestlerB */
        [$wrestlerA, $wrestlerB] = Wrestler::factory()->count(2)->create();

        return [
            'name' => fn () => Str::of(fake()->sentence(2))->title()->value(),
            'signature_move' => fn () => Str::of(fake()->optional(0.8)->sentence(3))->title()->value(),
            'employment_date' => fn () => $this->generateOptionalEmploymentDate(),
            'wrestlerA' => fn () => $wrestlerA->id,
            'wrestlerB' => fn () => $wrestlerB->id,
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
        return view($this->modalFormPath ?? 'livewire.tag-teams.modals.form-modal');
    }
}

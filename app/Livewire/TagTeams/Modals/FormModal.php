<?php

declare(strict_types=1);

namespace App\Livewire\TagTeams\Modals;

use App\Livewire\Base\BaseFormModal;
use App\Livewire\Concerns\Data\PresentsWrestlersList;
use App\Livewire\Concerns\GeneratesDummyData;
use App\Livewire\TagTeams\Forms\CreateEditForm;
use App\Models\Managers\Manager;
use App\Models\TagTeams\TagTeam;
use App\Models\Wrestlers\Wrestler;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Str;
use Illuminate\View\View;

/**
 * @extends BaseFormModal<CreateEditForm, TagTeam>
 */
class FormModal extends BaseFormModal
{
    use GeneratesDummyData;
    use PresentsWrestlersList;

    /**
     * @return Collection<int, Wrestler>
     */
    public function getWrestlersListProperty(): Collection
    {
        return Wrestler::all();
    }

    /**
     * @return array<int, string>
     */
    public function getManagersProperty(): array
    {
        return Manager::select('id', 'first_name', 'last_name')
            ->get()
            ->mapWithKeys(function (Manager $manager) {
                return [$manager->id => $manager->first_name.' '.$manager->last_name];
            })
            ->toArray();
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
            'start_date' => fn () => $this->generateOptionalStartDate(),
            'wrestlerA' => fn () => $wrestlerA->id,
            'wrestlerB' => fn () => $wrestlerB->id,
        ];
    }

    public function render(): View
    {
        return view($this->modalFormPath ?? 'livewire.tag-teams.modals.form-modal');
    }
}

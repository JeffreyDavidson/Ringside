<?php

declare(strict_types=1);

namespace App\Livewire\TagTeams\Modals;

use App\Livewire\Concerns\BaseModal;
use App\Livewire\TagTeams\TagTeamForm;
use App\Models\TagTeam;
use App\Models\Wrestler;
use App\Traits\Data\PresentsWrestlersList;
use Exception;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * @extends BaseModal<TagTeamForm, TagTeam>
 */
class FormModal extends BaseModal
{
    use PresentsWrestlersList;

    protected string $modalFormPath = 'tag-teams.modals.form-modal';

    protected $modelForm;

    protected $modelType;

    public function fillDummyFields(): void
    {
        if ($this->modelForm->formModel !== null) {
            throw new Exception('No need to fill data on an edit form.');
        }

        /** @var Carbon|null $datetime */
        $datetime = fake()->optional(0.8)->dateTimeBetween('now', '+3 month');

        /** @var Wrestler $wrestlerA */
        /** @var Wrestler $wrestlerB */
        [$wrestlerA, $wrestlerB] = Wrestler::factory()->count(2)->create();

        $this->modelForm->name = Str::of(fake()->sentence(2))->title()->value();
        $this->modelForm->signature_move = Str::of(fake()->optional(0.8)->sentence(3))->title()->value();
        $this->modelForm->start_date = $datetime?->format('Y-m-d H:i:s');
        $this->modelForm->wrestlerA = $wrestlerA->id;
        $this->modelForm->wrestlerB = $wrestlerB->id;
    }
}

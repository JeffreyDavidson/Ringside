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

class FormModal extends BaseModal
{
    use PresentsWrestlersList;

    protected string $modelType = TagTeam::class;

    protected string $modalLanguagePath = 'tag-teams';

    protected string $modalFormPath = 'tag-teams.modals.form-modal';

    public TagTeamForm $modelForm;

    public function fillDummyFields()
    {
        if (isset($this->modelForm->formModel)) {
            throw new Exception('No need to fill data on an edit form.');
        }

        $datetime = fake()->optional(0.8)->dateTimeBetween('now', '+3 month');
        [$wrestlerA, $wrestlerB] = Wrestler::factory()->count(2)->create();

        $this->modelForm->name = Str::title(fake()->words(2, true));
        $this->modelForm->signature_move = Str::title(fake()->optional(0.8)->words(3, true));
        $this->modelForm->start_date = $datetime ? Carbon::instance($datetime)->toDateString() : null;
        $this->modelForm->wrestlerA = $wrestlerA->id;
        $this->modelForm->wrestlerB = $wrestlerB->id;
    }
}

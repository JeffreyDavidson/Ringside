<?php

declare(strict_types=1);

namespace App\Livewire\Stables\Forms;

use App\Data\Stables\StableMembershipData;
use App\Models\Roster\TagTeams\TagTeam;
use App\Models\Roster\Wrestlers\Wrestler;
use Livewire\Form;

/**
 * The wrestlers and tag teams ticked in a stable modal. Whether each one may be moved is decided by the Action.
 */
abstract class MemberSelectionForm extends Form
{
    /** @var array<int, int|string> */
    public array $wrestlerIds = [];

    /** @var array<int, int|string> */
    public array $tagTeamIds = [];

    public function members(): StableMembershipData
    {
        return new StableMembershipData(
            wrestlers: Wrestler::query()->whereKey($this->wrestlerIds)->get(),
            tagTeams: TagTeam::query()->whereKey($this->tagTeamIds)->get(),
        );
    }

    /** @return array<string, array<int, string>> */
    protected function memberRules(): array
    {
        return [
            'wrestlerIds' => ['array'],
            'wrestlerIds.*' => ['integer'],
            'tagTeamIds' => ['array'],
            'tagTeamIds.*' => ['integer'],
        ];
    }
}

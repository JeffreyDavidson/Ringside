<?php

declare(strict_types=1);

namespace App\Livewire\Stables\Forms;

use App\Data\Stables\StableMembershipData;
use App\Models\Roster\TagTeams\TagTeam;
use App\Models\Roster\Wrestlers\Wrestler;

class ReuniteForm extends MemberSelectionForm
{
    public function selectAll(StableMembershipData $formerMembers): void
    {
        $this->wrestlerIds = $formerMembers->wrestlers?->map(fn (Wrestler $wrestler): int => $wrestler->id)->values()->all() ?? [];
        $this->tagTeamIds = $formerMembers->tagTeams?->map(fn (TagTeam $tagTeam): int => $tagTeam->id)->values()->all() ?? [];
    }

    public function validateForSubmit(): void
    {
        $this->validate($this->memberRules());
    }
}

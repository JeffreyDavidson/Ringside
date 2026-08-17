<?php

declare(strict_types=1);

namespace App\Livewire\Concerns\Data;

use App\Models\Roster\TagTeams\TagTeam;
use Livewire\Attributes\Computed;

trait PresentsTagTeamsList
{
    /**
     * @return array<int|string,string|null>
     */
    #[Computed(cache: true, key: 'tag-teams-list', seconds: 180)]
    public function getTagTeams(): array
    {
        return TagTeam::query()
            ->get(['id', 'name'])
            ->mapWithKeys(fn (TagTeam $tagTeam): array => [$tagTeam->id => $tagTeam->name])
            ->all();
    }
}

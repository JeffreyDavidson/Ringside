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
    #[Computed]
    public function getTagTeams(): array
    {
        return TagTeam::query()
            ->pluck('name', 'id')
            ->all();
    }
}

<?php

declare(strict_types=1);

namespace App\Actions\TagTeams;

use App\Models\TagTeams\TagTeam;
use App\Models\Wrestlers\Wrestler;
use Illuminate\Database\Eloquent\Collection;
use Lorisleiva\Actions\Concerns\AsAction;

class AddTagTeamPartnersAction
{
    use AsAction;

    /**
     * Update a given tag team with given wrestlers.
     *
     * @param  Collection<int, Wrestler>  $wrestlers
     */
    public function handle(TagTeam $tagTeam, Collection $wrestlers): void
    {
        $joinDate = now();

        foreach ($wrestlers as $wrestler) {
            $tagTeam->wrestlers()->attach($wrestler->id, [
                'joined_at' => $joinDate,
                'left_at' => null,
            ]);
        }
    }
}

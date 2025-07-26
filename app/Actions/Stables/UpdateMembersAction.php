<?php

declare(strict_types=1);

namespace App\Actions\Stables;

use App\Models\Stables\Stable;
use App\Models\TagTeams\TagTeam;
use App\Models\Wrestlers\Wrestler;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;
use Lorisleiva\Actions\Concerns\AsAction;

class UpdateMembersAction
{
    use AsAction;

    /**
     * Update a stable's members.
     *
     * Note: Managers are NOT directly associated with stables.
     * They are automatically associated through wrestlers/tag teams.
     *
     * @param  Collection<int, Wrestler>  $wrestlers
     * @param  Collection<int, TagTeam>  $tagTeams
     */
    public function handle(Stable $stable, Collection $wrestlers, Collection $tagTeams): void
    {
        $now = now();

        $this->updateWrestlers($stable, $wrestlers, $now);
        $this->updateTagTeams($stable, $tagTeams, $now);
    }

    /**
     * Update wrestlers attached to a stable.
     *
     * @param  Collection<int, Wrestler>  $wrestlers
     */
    private function updateWrestlers(Stable $stable, Collection $wrestlers, Carbon $now): void
    {
        if ($stable->currentWrestlers->isEmpty()) {
            foreach ($wrestlers as $wrestler) {
                $stable->wrestlers()->attach($wrestler->id, [
                    'joined_at' => $now,
                    'left_at' => null,
                ]);
            }
        } else {
            $currentWrestlers = $stable->currentWrestlers;
            $formerWrestlers = $currentWrestlers->diff($wrestlers);
            $newWrestlers = $wrestlers->diff($currentWrestlers);

            // Remove former wrestlers
            $formerWrestlers->each(function (Wrestler $wrestler) use ($stable, $now) {
                $stable->wrestlers()->updateExistingPivot($wrestler->id, [
                    'left_at' => $now,
                ]);
            });

            // Add new wrestlers
            foreach ($newWrestlers as $wrestler) {
                $stable->wrestlers()->attach($wrestler->id, [
                    'joined_at' => $now,
                    'left_at' => null,
                ]);
            }
        }
    }

    /**
     * Update tag teams attached to a stable.
     *
     * @param  Collection<int, TagTeam>  $tagTeams
     */
    private function updateTagTeams(Stable $stable, Collection $tagTeams, Carbon $now): void
    {
        if ($stable->currentTagTeams->isEmpty()) {
            foreach ($tagTeams as $tagTeam) {
                $stable->tagTeams()->attach($tagTeam->id, [
                    'joined_at' => $now,
                    'left_at' => null,
                ]);
            }
        } else {
            $currentTagTeams = $stable->currentTagTeams;
            $formerTagTeams = $currentTagTeams->diff($tagTeams);
            $newTagTeams = $tagTeams->diff($currentTagTeams);

            // Remove former tag teams
            $formerTagTeams->each(function (TagTeam $tagTeam) use ($stable, $now) {
                $stable->tagTeams()->updateExistingPivot($tagTeam->id, [
                    'left_at' => $now,
                ]);
            });

            // Add new tag teams
            foreach ($newTagTeams as $tagTeam) {
                $stable->tagTeams()->attach($tagTeam->id, [
                    'joined_at' => $now,
                    'left_at' => null,
                ]);
            }
        }
    }
}

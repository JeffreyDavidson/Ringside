<?php

declare(strict_types=1);

namespace App\Actions\TagTeams;

use App\Models\TagTeams\TagTeam;
use App\Models\Wrestlers\Wrestler;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;
use Lorisleiva\Actions\Concerns\AsAction;

class UpdateTagTeamPartnersAction
{
    use AsAction;

    /**
     * Update a given tag team with given wrestlers.
     *
     * @param  Collection<int, Wrestler>  $wrestlers
     */
    public function handle(TagTeam $tagTeam, Collection $wrestlers, ?Carbon $joinDate = null): void
    {
        $joinDate ??= now();

        if ($tagTeam->currentWrestlers->isEmpty()) {
            if ($wrestlers->isNotEmpty()) {
                foreach ($wrestlers as $wrestler) {
                    $tagTeam->wrestlers()->attach($wrestler->id, [
                        'joined_at' => $joinDate,
                        'left_at' => null,
                    ]);
                }
            }
        } else {
            /** @var Collection<int, Wrestler> $formerTagTeamPartners */
            $formerTagTeamPartners = $tagTeam->currentWrestlers()->wherePivotNotIn(
                'wrestler_id',
                $wrestlers->modelKeys()
            )->get();

            $newTagTeamPartners = $wrestlers->except($formerTagTeamPartners->modelKeys());

            // End partnerships for former partners
            $formerTagTeamPartners->each(function (Wrestler $wrestler) use ($tagTeam, $joinDate) {
                $tagTeam->wrestlers()->updateExistingPivot($wrestler->id, [
                    'left_at' => $joinDate,
                ]);
            });

            // Add new partners
            foreach ($newTagTeamPartners as $wrestler) {
                $tagTeam->wrestlers()->attach($wrestler->id, [
                    'joined_at' => $joinDate,
                    'left_at' => null,
                ]);
            }
        }
    }
}

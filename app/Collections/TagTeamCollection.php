<?php

declare(strict_types=1);

namespace App\Collections;

use App\Models\Roster\TagTeams\TagTeam;
use App\Models\Roster\Wrestlers\Wrestler;
use Illuminate\Database\Eloquent\Collection;
use LogicException;

/** @extends Collection<int, TagTeam> */
class TagTeamCollection extends Collection
{
    /**
     * The current wrestlers of every tag team, one after the other. Callers load `currentWrestlers` first (a
     * collection never queries); a tag team without the relation loaded fails loudly instead of lazy loading.
     *
     * @return Collection<int, Wrestler>
     *
     * @throws LogicException
     */
    public function currentWrestlers(): Collection
    {
        $wrestlers = $this->flatMap(function (TagTeam $tagTeam): array {
            if (! $tagTeam->relationLoaded('currentWrestlers')) {
                throw new LogicException('Load the currentWrestlers relation before reading it from a tag team collection.');
            }

            return $tagTeam->currentWrestlers->all();
        });

        return (new Wrestler)->newCollection($wrestlers->all());
    }
}

<?php

declare(strict_types=1);

namespace App\Actions\TagTeams;

use App\Data\TagTeamData;
use App\Models\TagTeam;
use App\Models\Wrestler;
use Illuminate\Support\Carbon;
use Lorisleiva\Actions\Concerns\AsAction;

class UpdateAction extends BaseTagTeamAction
{
    use AsAction;

    /**
     * Update a tag team.
     */
    public function handle(TagTeam $tagTeam, TagTeamData $tagTeamData): TagTeam
    {
        $this->tagTeamRepository->update($tagTeam, $tagTeamData);
        $datetime = now();

        if ($tagTeamData->wrestlerA && $tagTeamData->wrestlerA->currentTagTeam?->isNot($tagTeam)) {
            $this->tagTeamRepository->addTagTeamPartner($tagTeam, $tagTeamData->wrestlerA, $datetime);
        }

        if ($tagTeamData->wrestlerB && $tagTeamData->wrestlerB->currentTagTeam?->isNot($tagTeam)) {
            $this->tagTeamRepository->addTagTeamPartner($tagTeam, $tagTeamData->wrestlerB, $datetime);
        }

        if ($tagTeam->currentWrestlers->isNotEmpty()) {
            $tagTeam
                ->currentWrestlers
                ->reject(fn (Wrestler $wrestler) => in_array($wrestler, [$tagTeamData->wrestlerA, $tagTeamData->wrestlerB]))
                ->each(fn (Wrestler $wrestler) => $this->tagTeamRepository->removeTagTeamPartner($tagTeam, $wrestler, $datetime));
        }

        if ($this->shouldBeEmployed($tagTeam, $tagTeamData->start_date)) {
            $this->tagTeamRepository->employ($tagTeam, $tagTeamData->start_date);
        }

        return $tagTeam;
    }

    /**
     * Find out if the tag team can be employed.
     */
    private function shouldBeEmployed(TagTeam $tagTeam, ?Carbon $startDate): bool
    {
        if (is_null($startDate)) {
            return false;
        }

        if ($tagTeam->isCurrentlyEmployed()) {
            return false;
        }

        return true;
    }
}

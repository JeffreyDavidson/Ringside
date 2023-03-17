<?php

declare(strict_types=1);

namespace App\Actions\TagTeams;

use App\Data\TagTeamData;
use App\Models\TagTeam;
use App\Models\Wrestler;
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

        if ($tagTeamData->wrestlerA && $tagTeamData->wrestlerA->currentTagTeam?->isNot($tagTeam)) {
            $this->tagTeamRepository->addTagTeamPartner($tagTeam, $tagTeamData->wrestlerA);
        }

        if ($tagTeamData->wrestlerB && $tagTeamData->wrestlerB->currentTagTeam?->isNot($tagTeam)) {
            $this->tagTeamRepository->addTagTeamPartner($tagTeam, $tagTeamData->wrestlerB);
        }

        if ($tagTeam->currentWrestlers->isNotEmpty()) {
            $tagTeam
                ->currentWrestlers
                ->reject(fn (Wrestler $wrestler) => in_array($wrestler, [$tagTeamData->wrestlerA, $tagTeamData->wrestlerB]))
                ->each(fn (Wrestler $wrestler) => $this->tagTeamRepository->removeTagTeamPartner($tagTeam, $wrestler, now()));
        }

        if (isset($tagTeamData->start_date)) {
            if ($tagTeam->canBeEmployed()
                || $tagTeam->canHaveEmploymentStartDateChanged($tagTeamData->start_date)
            ) {
                $this->tagTeamRepository->employ($tagTeam, $tagTeamData->start_date);
            }
        }

        return $tagTeam;
    }
}

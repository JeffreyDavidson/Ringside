<?php

declare(strict_types=1);

namespace App\Actions\TagTeams;

use App\Data\TagTeamData;
use App\Models\TagTeam;
use Lorisleiva\Actions\Concerns\AsAction;

class UpdateAction extends BaseTagTeamAction
{
    use AsAction;

    /**
     * Update a tag team.
     *
     * @param  \App\Models\TagTeam  $tagTeam
     * @param  \App\Data\TagTeamData  $tagTeamData
     * @return \App\Models\TagTeam
     */
    public function handle(TagTeam $tagTeam, TagTeamData $tagTeamData): TagTeam
    {
        $this->tagTeamRepository->update($tagTeam, $tagTeamData);

        if ($tagTeamData->wrestlers->isNotEmpty()) {
            UpdateTagTeamPartnersAction::run($tagTeam, $tagTeamData->wrestlers);
        }

        if (isset($tagTeamData->start_date)) {
            if ($tagTeam->canBeEmployed()
                || $tagTeam->canHaveEmploymentStartDateChanged($tagTeamData->start_date)
            ) {
                EmployAction::run($tagTeam, $tagTeamData->start_date);
            }
        }

        return $tagTeam;
    }
}

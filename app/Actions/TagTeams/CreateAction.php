<?php

declare(strict_types=1);

namespace App\Actions\TagTeams;

use App\Data\TagTeamData;
use App\Models\TagTeam;
use Lorisleiva\Actions\Concerns\AsAction;

class CreateAction extends BaseTagTeamAction
{
    use AsAction;

    /**
     * Create a tag team.
     */
    public function handle(TagTeamData $tagTeamData): TagTeam
    {
        /** @var TagTeam $tagTeam */
        $tagTeam = $this->tagTeamRepository->create($tagTeamData);

        $datetime = now();

        if ($tagTeamData->wrestlerA) {
            $this->tagTeamRepository->addTagTeamPartner($tagTeam, $tagTeamData->wrestlerA, $datetime);
        }

        if ($tagTeamData->wrestlerB) {
            $this->tagTeamRepository->addTagTeamPartner($tagTeam, $tagTeamData->wrestlerB, $datetime);
        }

        if (isset($tagTeamData->start_date)) {
            $this->tagTeamRepository->employ($tagTeam, $tagTeamData->start_date);
        }

        return $tagTeam;
    }
}

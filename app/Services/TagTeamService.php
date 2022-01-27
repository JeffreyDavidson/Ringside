<?php

namespace App\Services;

use App\Actions\TagTeams\AddTagTeamPartnersAction;
use App\Actions\TagTeams\EmployAction;
use App\Actions\TagTeams\UpdateTagTeamPartnersAction;
use App\DataTransferObjects\TagTeamData;
use App\Models\TagTeam;
use App\Repositories\TagTeamRepository;
use App\Repositories\WrestlerRepository;

class TagTeamService
{
    /**
     * The repository implementation.
     *
     * @var \App\Repositories\TagTeamRepository
     */
    protected $tagTeamRepository;

    /**
     * The repository implementation.
     *
     * @var \App\Repositories\WrestlerRepository
     */
    protected $wrestlerRepository;

    /**
     * Create a new tag team service instance.
     *
     * @param \App\Repositories\TagTeamRepository $tagTeamRepository
     * @param \App\Repositories\WrestlerRepository $wrestlerRepository
     */
    public function __construct(TagTeamRepository $tagTeamRepository, WrestlerRepository $wrestlerRepository)
    {
        $this->tagTeamRepository = $tagTeamRepository;
        $this->wrestlerRepository = $wrestlerRepository;
    }

    /**
     * Create a tag team with given data.
     *
     * @param  \App\DataTransferObjects\TagTeamData $tagTeamData
     *
     * @return \App\Models\TagTeam
     */
    public function create(TagTeamData $tagTeamData)
    {
        /** @var \App\Models\TagTeam $tagTeam */
        $tagTeam = $this->tagTeamRepository->create($tagTeamData);

        if ($tagTeamData->wrestlers->isNotEmpty()) {
            AddTagTeamPartnersAction::run($tagTeam, $tagTeamData->wrestlers, now());
        }

        if (isset($tagTeamData->start_date)) {
            EmployAction::run($tagTeam, $tagTeamData->start_date);
        }

        return $tagTeam;
    }

    /**
     * Update a given tag team with given data.
     *
     * @param  \App\Models\TagTeam $tagTeam
     * @param  \App\DataTransferObjects\TagTeamData $tagTeamData
     *
     * @return \App\Models\TagTeam
     */
    public function update(TagTeam $tagTeam, TagTeamData $tagTeamData)
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

    /**
     * Delete a given tag team.
     *
     * @param  \App\Models\TagTeam $tagTeam
     *
     * @return void
     */
    public function delete(TagTeam $tagTeam)
    {
        $this->tagTeamRepository->delete($tagTeam);
    }

    /**
     * Restore a given tag team.
     *
     * @param  \App\Models\TagTeam $tagTeam
     *
     * @return void
     */
    public function restore(TagTeam $tagTeam)
    {
        $this->tagTeamRepository->restore($tagTeam);
    }
}

<?php

namespace App\Services;

use App\Actions\Referees\EmployAction;
use App\Data\RefereeData;
use App\Models\Referee;
use App\Repositories\RefereeRepository;

class RefereeService
{
    /**
     * The repository implementation.
     *
     * @var \App\Repositories\RefereeRepository
     */
    protected $refereeRepository;

    /**
     * Create a new referee service instance.
     *
     * @param \App\Repositories\RefereeRepository $refereeRepository
     */
    public function __construct(RefereeRepository $refereeRepository)
    {
        $this->refereeRepository = $refereeRepository;
    }

    /**
     * Create a referee with given data.
     *
     * @param  \App\Data\RefereeData $refereeData
     * @return \App\Models\Referee
     */
    public function create(RefereeData $refereeData)
    {
        /** @var \App\Models\Referee $referee */
        $referee = $this->refereeRepository->create($refereeData);

        if (isset($refereeData->start_date)) {
            EmployAction::run($referee, $refereeData->start_date);
        }

        return $referee;
    }

    /**
     * Update a given referee with given data.
     *
     * @param  \App\Models\Referee $referee
     * @param  \App\Data\RefereeData $refereeData
     * @return \App\Models\Referee
     */
    public function update(Referee $referee, RefereeData $refereeData)
    {
        $this->refereeRepository->update($referee, $refereeData);

        if (isset($refereeData->start_date)) {
            if ($referee->canBeEmployed()
                || $referee->canHaveEmploymentStartDateChanged($refereeData->start_date)
            ) {
                EmployAction::run($referee, $refereeData->start_date);
            }
        }

        return $referee;
    }

    /**
     * Delete a given referee.
     *
     * @param  \App\Models\Referee $referee
     * @return void
     */
    public function delete(Referee $referee)
    {
        $this->refereeRepository->delete($referee);
    }

    /**
     * Restore a given referee.
     *
     * @param  \App\Models\Referee $referee
     * @return void
     */
    public function restore(Referee $referee)
    {
        $this->refereeRepository->restore($referee);
    }
}

<?php

namespace App\Services;

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
     * @param  array $data
     * @return \App\Models\Referee $referee
     */
    public function create(array $data)
    {
        $referee = $this->refereeRepository->create($data);

        if (isset($data['started_at'])) {
            $this->refereeRepository->employ($referee, $data['started_at']);
        }

        return $referee;
    }

    /**
     * Update a given referee with given data.
     *
     * @param  \App\Models\Referee $referee
     * @param  array $data
     * @return \App\Models\Referee $referee
     */
    public function update(Referee $referee, array $data)
    {
        $this->refereeRepository->update($referee, $data);

        if ($referee->canHaveEmploymentStartDateChanged() && isset($data['started_at'])) {
            $this->employOrUpdateEmployment($referee, $data['started_at']);
        }

        return $referee;
    }

    /**
     * Employ a given referee or update the given referee's employment date.
     *
     * @param  \App\Models\Referee $referee
     * @param  string $employmentDate
     * @return void
     */
    public function employOrUpdateEmployment(Referee $referee, string $employmentDate)
    {
        if ($referee->isNotInEmployment()) {
            return $this->refereeRepository->employ($referee, $employmentDate);
        }

        if ($referee->hasFutureEmployment() && ! $referee->employedOn($employmentDate)) {
            return $this->refereeRepository->updateEmployment($referee, $employmentDate);
        }
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

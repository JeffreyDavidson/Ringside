<?php

namespace App\Services;

use App\Models\Wrestler;
use App\Repositories\WrestlerRepository;

class WrestlerService
{
    /**
     * The repository implementation.
     *
     * @var \App\Repositories\WrestlerRepository
     */
    protected $wrestlerRepository;

    /**
     * Create a new wrestler service instance.
     *
     * @param \App\Repositories\WrestlerRepository $wrestlerRepository
     */
    public function __construct(WrestlerRepository $wrestlerRepository)
    {
        $this->wrestlerRepository = $wrestlerRepository;
    }

    /**
     * Create a new wrestler with given data.
     *
     * @param  array $data
     * @return \App\Models\Wrestler $wrestler
     */
    public function create(array $data)
    {
        $wrestler = $this->wrestlerRepository->create($data);

        if (isset($data['started_at'])) {
            $this->wrestlerRepository->employ($wrestler, $data['started_at']);
        }

        return $wrestler;
    }

    /**
     * Update a given wrestler with given data.
     *
     * @param  \App\Models\Wrestler $wrestler
     * @param  array $data
     * @return \App\Models\Wrestler $wrestler
     */
    public function update(Wrestler $wrestler, array $data)
    {
        $this->wrestlerRepository->update($wrestler, $data);

        if ($wrestler->canHaveEmploymentStartDateChanged() && isset($data['started_at'])) {
            $this->employOrUpdateEmployment($wrestler, $data['started_at']);
        }

        return $wrestler;
    }

    /**
     * Employ a given wrestler or update the given wrestler's employment date.
     *
     * @param  \App\Models\Wrestler $wrestler
     * @param  string $employmentDate
     * @return void
     */
    public function employOrUpdateEmployment(Wrestler $wrestler, string $employmentDate)
    {
        if ($wrestler->isNotInEmployment()) {
            return $this->wrestlerRepository->employ($wrestler, $employmentDate);
        }

        if ($wrestler->hasFutureEmployment() && ! $wrestler->employedOn($employmentDate)) {
            return $this->wrestlerRepository->updateEmployment($wrestler, $employmentDate);
        }
    }

    /**
     * Delete a given wrestler.
     *
     * @param  \App\Models\Wrestler $wrestler
     * @return void
     */
    public function delete(Wrestler $wrestler)
    {
        $this->wrestlerRepository->delete($wrestler);
    }

    /**
     * Restore a given wrestler.
     *
     * @param  \App\Models\Wrestler $wrestler
     * @return void
     */
    public function restore(Wrestler $wrestler)
    {
        $this->wrestlerRepository->restore($wrestler);
    }
}

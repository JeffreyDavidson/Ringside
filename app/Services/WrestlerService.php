<?php

declare(strict_types=1);

namespace App\Services;

use App\Actions\Wrestlers\EmployAction;
use App\Data\WrestlerData;
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
     * @param  \App\Data\WrestlerData $wrestlerData
     * @return \App\Models\Wrestler
     */
    public function create(WrestlerData $wrestlerData)
    {
        /** @var \App\Models\Wrestler $wrestler */
        $wrestler = $this->wrestlerRepository->create($wrestlerData);

        if (isset($wrestlerData->start_date)) {
            EmployAction::run($wrestler, $wrestlerData->start_date);
        }

        return $wrestler;
    }

    /**
     * Update a given wrestler with given data.
     *
     * @param  \App\Models\Wrestler $wrestler
     * @param  \App\Data\WrestlerData $wrestlerData
     * @return \App\Models\Wrestler
     */
    public function update(Wrestler $wrestler, WrestlerData $wrestlerData)
    {
        $this->wrestlerRepository->update($wrestler, $wrestlerData);

        if (isset($wrestlerData->start_date)) {
            if ($wrestler->canBeEmployed()
                || $wrestler->canHaveEmploymentStartDateChanged($wrestlerData->start_date)
            ) {
                EmployAction::run($wrestler, $wrestlerData->start_date);
            }
        }

        return $wrestler;
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

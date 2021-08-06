<?php

namespace App\Services;

use App\Models\Wrestler;
use App\Repositories\WrestlerRepository;
use App\Strategies\ClearInjury\WrestlerClearInjuryStrategy;
use App\Strategies\Employment\WrestlerEmploymentStrategy;
use App\Strategies\Injure\WrestlerInjuryStrategy;
use App\Strategies\Reinstate\WrestlerReinstateStrategy;
use App\Strategies\Release\WrestlerReleaseStrategy;
use App\Strategies\Retirement\WrestlerRetirementStrategy;
use App\Strategies\Suspend\WrestlerSuspendStrategy;
use App\Strategies\Unretire\WrestlerUnretireStrategy;
use Carbon\Carbon;

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
     * Create a new wrestler.
     *
     * @param  array $data
     * @return \App\Models\Wrestler $wrestler
     */
    public function create(array $data)
    {
        $wrestler = $this->wrestlerRepository->create($data);

        if ($data['started_at']) {
            (new WrestlerEmploymentStrategy($wrestler))->employ(Carbon::parse($data['started_at']));
        }

        return $wrestler;
    }

    /**
     * Update a wrestler.
     *
     * @param  \App\Models\Wrestler $wrestler
     * @param  array $data
     * @return \App\Models\Wrestler $wrestler
     */
    public function update(Wrestler $wrestler, array $data)
    {
        $this->wrestlerRepository->update($wrestler, $data);

        if ($data['started_at']) {
            $this->employOrUpdateEmployment($wrestler, Carbon::parse($data['started_at']));
        }

        return $wrestler;
    }

    /**
     * Undocumented function
     *
     * @param  \App\Models\Wrestler $wrestler
     * @param  \Carbon\Carbon $startedAt
     * @return void
     */
    public function employOrUpdateEmployment(Wrestler $wrestler, Carbon $startedAt)
    {
        if ($wrestler->isUnemployed()) {
            return (new WrestlerEmploymentStrategy($wrestler))->employ(Carbon::parse($startedAt));
        }

        if ($wrestler->hasFutureEmployment() && $wrestler->futureEmployment->started_at->ne($startedAt)) {
            return $wrestler->futureEmployment()->update(['started_at' => $startedAt]);
        }
    }

    /**
     * Clear an injury of a wrestler.
     *
     * @param  \App\Models\Wrestler $wrestler
     * @return void
     */
    public function clearFromInjury(Wrestler $wrestler)
    {
        (new WrestlerClearInjuryStrategy($wrestler))->clearInjury();
    }

    /**
     * Injure a wrestler.
     *
     * @param  \App\Models\Wrestler $wrestler
     * @return void
     */
    public function injure(Wrestler $wrestler)
    {
        (new WrestlerInjuryStrategy($wrestler))->injure();
    }

    /**
     * Employ a wrestler.
     *
     * @param  \App\Models\Wrestler $wrestler
     * @return void
     */
    public function employ(Wrestler $wrestler)
    {
        (new WrestlerEmploymentStrategy($wrestler))->employ();
    }

    /**
     * Unretire a wrestler.
     *
     * @param  \App\Models\Wrestler $wrestler
     * @return void
     */
    public function suspend(Wrestler $wrestler)
    {
        (new WrestlerSuspendStrategy($wrestler))->suspend();
    }

    /**
     * Reinstate a wrestler.
     *
     * @param  \App\Models\Wrestler $wrestler
     * @return void
     */
    public function reinstate(Wrestler $wrestler)
    {
        (new WrestlerReinstateStrategy($wrestler))->reinstate();
    }

    /**
     * Retire a wrestler.
     *
     * @param  \App\Models\Wrestler $wrestler
     * @return void
     */
    public function retire(Wrestler $wrestler)
    {
        (new WrestlerRetirementStrategy($wrestler))->retire();
    }

    /**
     * Unretire a wrestler.
     *
     * @param  \App\Models\Wrestler $wrestler
     * @return void
     */
    public function unretire(Wrestler $wrestler)
    {
        (new WrestlerUnretireStrategy($wrestler))->unretire();
    }

    /**
     * Release a wrestler.
     *
     * @param  \App\Models\Wrestler $wrestler
     * @return void
     */
    public function release(Wrestler $wrestler)
    {
        (new WrestlerReleaseStrategy($wrestler))->release();
    }
}

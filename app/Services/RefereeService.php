<?php

namespace App\Services;

use App\Models\Referee;
use App\Repositories\RefereeRepository;
use App\Strategies\ClearInjury\RefereeClearInjuryStrategy;
use App\Strategies\Employment\RefereeEmploymentStrategy;
use App\Strategies\Injure\RefereeInjuryStrategy;
use App\Strategies\Reinstate\RefereeReinstateStrategy;
use App\Strategies\Release\RefereeReleaseStrategy;
use App\Strategies\Retirement\RefereeRetirementStrategy;
use App\Strategies\Suspend\RefereeSuspendStrategy;
use App\Strategies\Unretire\RefereeUnretireStrategy;
use Carbon\Carbon;

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
     * Create a referee.
     *
     * @param  array $data
     * @return \App\Models\Referee $referee
     */
    public function create(array $data)
    {
        $referee = $this->refereeRepository->create($data);

        if ($data['started_at']) {
            (new RefereeEmploymentStrategy($referee))->employ(Carbon::parse($data['started_at']));
        }

        return $referee;
    }

    /**
     * Update a referee.
     *
     * @param  \App\Models\Referee $referee
     * @param  array $data
     * @return \App\Models\Referee $referee
     */
    public function update(Referee $referee, array $data)
    {
        $this->refereeRepository->update($referee, $data);

        if ($data['started_at']) {
            $this->employOrUpdateEmployment($referee, Carbon::parse($data['started_at']));
        }

        return $referee;
    }

    public function employOrUpdateEmployment(Referee $referee, Carbon $startedAt)
    {
        if ($referee->isUnemployed()) {
            (new RefereeEmploymentStrategy($referee))->employ($startedAt);
        }

        if ($referee->hasFutureEmployment() && $referee->futureEmployment->started_at->ne($startedAt)) {
            return $referee->futureEmployment()->update(['started_at' => $startedAt]);
        }
    }

    /**
     * Clear an injury of a referee.
     *
     * @param  \App\Models\Referee $referee
     * @return void
     */
    public function clearFromInjury(Referee $referee)
    {
        (new RefereeClearInjuryStrategy($referee))->clearInjury();
    }

    /**
     * Injure a referee.
     *
     * @param  \App\Models\Referee $referee
     * @return void
     */
    public function injure(Referee $referee)
    {
        (new RefereeInjuryStrategy($referee))->injure();
    }

    /**
     * Employ a referee.
     *
     * @param  \App\Models\Referee $referee
     * @return void
     */
    public function employ(Referee $referee)
    {
        (new RefereeEmploymentStrategy($referee))->employ();
    }

    /**
     * Employ a referee.
     *
     * @param  \App\Models\Referee $referee
     * @return void
     */
    public function reinstate(Referee $referee)
    {
        (new RefereeReinstateStrategy($referee))->reinstate();
    }

    /**
     * Unretire a referee.
     *
     * @param  \App\Models\Referee $referee
     * @return void
     */
    public function unretire(Referee $referee)
    {
        (new RefereeUnretireStrategy($referee))->unretire();
    }

    /**
     * Unretire a referee.
     *
     * @param  \App\Models\Referee $referee
     * @return void
     */
    public function suspend(Referee $referee)
    {
        (new RefereeSuspendStrategy($referee))->suspend();
    }

    /**
     * Retire a referee.
     *
     * @param  \App\Models\Referee $referee
     * @return void
     */
    public function retire(Referee $referee)
    {
        (new RefereeRetirementStrategy($referee))->retire();
    }

    /**
     * Release a referee.
     *
     * @param  \App\Models\Referee $referee
     * @return void
     */
    public function release(Referee $referee)
    {
        (new RefereeReleaseStrategy($referee))->release();
    }
}

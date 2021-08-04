<?php

namespace App\Services;

use App\Exceptions\CannotBeClearedFromInjuryException;
use App\Exceptions\CannotBeEmployedException;
use App\Exceptions\CannotBeInjuredException;
use App\Exceptions\CannotBeReinstatedException;
use App\Exceptions\CannotBeReleasedException;
use App\Exceptions\CannotBeRetiredException;
use App\Exceptions\CannotBeSuspendedException;
use App\Exceptions\CannotBeUnretiredException;
use App\Models\Referee;
use App\Repositories\RefereeRepository;
use Carbon\Carbon;

class RefereeService
{
    protected $refereeRepository;

    public function __construct(RefereeRepository $refereeRepository)
    {
        $this->refereeRepository = $refereeRepository;
    }

    /**
     * Creates a new tag team.
     *
     * @param  array $data
     * @return \App\Models\Referee $referee
     */
    public function create(array $data): Referee
    {
        $referee = $this->refereeRepository->create($data);

        if ($data['started_at']) {
            $this->employ($referee, $data['started_at']);
        }

        return $referee;
    }

    /**
     * Updates a new referee.
     *
     * @param  \App\Models\Referee $referee
     * @param  array $data
     * @return \App\Models\Referee $referee
     */
    public function update(Referee $referee, array $data): Referee
    {
        $referee->update([
            'name' => $data['name'],
            'height' => $data['height'],
            'weight' => $data['weight'],
            'hometown' => $data['hometown'],
            'signature_move' => $data['signature_move'],
        ]);

        if ($data['started_at']) {
            $this->employOrUpdateEmployment($referee, $data['started_at']);
        }

        return $referee;
    }

    public function employOrUpdateEmployment(Referee $referee, $startedAt)
    {
        if ($referee->isUnemployed()) {
            return $this->employ($referee, $startedAt);
        }

        if ($referee->hasFutureEmployment() && $referee->futureEmployment->started_at->ne($startedAt)) {
            return $referee->futureEmployment()->update(['started_at' => $startedAt]);
        }
    }

/**
     * Employ a model.
     *
     * @param  \App\Models\Referee, $referee
     * @param  string|null $startedAt
     * @return void
     */
    public function employ($referee, $startedAt = null)
    {
        throw_unless($referee->canBeEmployed(), new CannotBeEmployedException);

        $startDate = Carbon::parse($startedAt)->toDayDateTimeString('minute') ?? now()->toDateTimeString('minute');

        $referee->employments()->updateOrCreate(['ended_at' => null], ['started_at' => $startDate]);
        $referee->updateStatusAndSave();
    }

    /**
     * Release a model.
     *
     * @param  \App\Models\Referee, $referee
     * @param  string|null $releasedAt
     * @return void
     */
    public function release($referee, $releasedAt = null)
    {
        throw_unless($referee->canBeReleased(), new CannotBeReleasedException);

        if ($referee->isSuspended()) {
            $this->reinstate($referee);
        }

        if ($referee->isInjured()) {
            $this->clearFromInjury($referee);
        }

        $releaseDate = Carbon::parse($releasedAt)->toDateTimeString('minute') ?? now()->toDateTimeString('minute');

        $referee->currentEmployment->update(['ended_at' => $releaseDate]);
        $referee->updateStatusAndSave();

        if ($referee->currentTagTeam) {
            $referee->currentTagTeam->updateStatusAndSave();
        }
    }

    /**
     * Injure a model.
     *
     * @param  \App\Models\Referee, $referee
     * @param  string|null $injuredAt
     * @return void
     */
    public function injure($referee, $injuredAt = null)
    {
        throw_unless($referee->canBeInjured(), new CannotBeInjuredException);

        $injuredDate = Carbon::parse($injuredAt)->toDateTimeString('minute') ?? now()->toDateTimeString('minute');

        $referee->injuries()->create(['started_at' => $injuredDate]);
        $referee->updateStatusAndSave();

        if ($referee->currentTagTeam) {
            $referee->currentTagTeam->updateStatusAndSave();
        }
    }

    /**
     * Clear a model from an injury.
     *
     * @param  \App\Models\Referee, $referee
     * @param  string|null $recoveredAt
     * @return void
     */
    public function clearFromInjury($referee, $recoveredAt = null)
    {
        throw_unless($referee->canBeClearedFromInjury(), new CannotBeClearedFromInjuryException);

        $recoveryDate = Carbon::parse($recoveredAt)->toDateTImeString('minute') ?? now()->toDateTimeString('minute');

        $referee->currentInjury()->update(['ended_at' => $recoveryDate]);
        $referee->updateStatusAndSave();

        if ($referee->currentTagTeam) {
            $referee->currentTagTeam->updateStatusAndSave();
        }
    }

    /**
     * Retire a model.
     *
     * @param  \App\Models\Referee, $referee
     * @param  string|null $retiredAt
     * @return void
     */
    public function retire($referee, $retiredAt = null)
    {
        throw_unless($referee->canBeRetired(), new CannotBeRetiredException);

        if ($referee->isSuspended()) {
            $this->reinstate($referee);
        }

        if ($referee->isInjured()) {
            $this->clearFromInjury($referee);
        }

        $retiredDate = Carbon::parse($retiredAt)->toDateTimeString('minute') ?: now()->toDateTimeString('minute');

        $referee->currentEmployment()->update(['ended_at' => $retiredDate]);
        $referee->retirements()->create(['started_at' => $retiredDate]);
        $referee->updateStatusAndSave();

        if ($referee->currentTagTeam) {
            $referee->currentTagTeam->updateStatusAndSave();
        }
    }

    /**
     * Unretire a model.
     *
     * @param  \App\Models\Referee, $referee
     * @param  string|null $unretiredAt
     * @return void
     */
    public function unretire($referee, $unretiredAt = null)
    {
        throw_unless($referee->canBeUnretired(), new CannotBeUnretiredException);

        $unretiredDate = Carbon::parse($unretiredAt)->toDateTimeString('minute') ?: now()->toDateTimeString('minute');

        $referee->currentRetirement()->update(['ended_at' => $unretiredDate]);
        $referee->employments()->create(['started_at' => $unretiredDate]);
        $referee->updateStatusAndSave();
    }

    /**
     * Suspend a model.
     *
     * @param  \App\Models\Referee, $referee
     * @param  string|null $suspendedAt
     * @return void
     */
    public function suspend($referee, $suspendedAt = null)
    {
        throw_unless($referee->canBeSuspended(), new CannotBeSuspendedException);

        $suspensionDate = Carbon::parse($suspendedAt)->toDateTimeString('minute') ?? now()->toDateTimeString('minute');

        $referee->suspensions()->create(['started_at' => $suspensionDate]);
        $referee->updateStatusAndSave();

        if ($referee->currentTagTeam) {
            $referee->currentTagTeam->updateStatusAndSave();
        }
    }

    /**
     * Reinstate a model.
     *
     * @param  \App\Models\Referee, $referee
     * @param  string|null $reinstatedAt
     * @return void
     */
    public function reinstate($referee, $reinstatedAt = null)
    {
        throw_unless($referee->canBeReinstated(), new CannotBeReinstatedException);

        $reinstatedDate = Carbon::parse($reinstatedAt)->toDateTimeString('minute') ?: now()->toDateTimeString('minute');

        $referee->currentSuspension()->update(['ended_at' => $reinstatedDate]);
        $referee->updateStatusAndSave();

        if ($referee->currentTagTeam) {
            $referee->currentTagTeam->updateStatusAndSave();
        }
    }
}

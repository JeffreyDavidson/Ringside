<?php

namespace App\Services;

use App\Models\Wrestler;
use App\Exceptions\CannotBeClearedFromInjuryException;
use App\Exceptions\CannotBeEmployedException;
use App\Exceptions\CannotBeInjuredException;
use App\Exceptions\CannotBeReinstatedException;
use App\Exceptions\CannotBeReleasedException;
use App\Exceptions\CannotBeRetiredException;
use App\Exceptions\CannotBeSuspendedException;
use App\Exceptions\CannotBeUnretiredException;
use Carbon\Carbon;

class WrestlerService
{
    /**
     * Creates a new tag team.
     *
     * @param  array $data
     * @return \App\Models\Wrestler $wrestler
     */
    public function create(array $data): Wrestler
    {
        $wrestler = Wrestler::create(['name' => $data['name'], 'height' => $data['height'], 'weight' => $data['weight'], 'hometown' => $data['hometown'], 'signature_move' => $data['signature_move']]);

        if ($data['started_at']) {
            $this->employ($wrestler, $data['started_at']);
        }

        return $wrestler;
    }

    /**
     * Updates a new wrestler.
     *
     * @param  \App\Models\Wrestler $wrestler
     * @param  array $data
     * @return \App\Models\Wrestler $wrestler
     */
    public function update(Wrestler $wrestler, array $data): Wrestler
    {
        $wrestler->update([
            'name' => $data['name'],
            'height' => $data['height'],
            'weight' => $data['weight'],
            'hometown' => $data['hometown'],
            'signature_move' => $data['signature_move'],
        ]);

        if ($data['started_at']) {
            $this->employOrUpdateEmployment($wrestler, $data['started_at']);
        }

        return $wrestler;
    }

    public function employOrUpdateEmployment(Wrestler $wrestler, $startedAt)
    {
        if ($wrestler->isUnemployed()) {
            return $this->employ($wrestler, $startedAt);
        }

        if ($wrestler->hasFutureEmployment() && $wrestler->futureEmployment->started_at->ne($startedAt)) {
            return $wrestler->futureEmployment()->update(['started_at' => $startedAt]);
        }
    }

/**
     * Employ a model.
     *
     * @param  \App\Models\Wrestler, $wrestler
     * @param  string|null $startedAt
     * @return void
     */
    public function employ($wrestler, $startedAt = null)
    {
        throw_unless($wrestler->canBeEmployed(), new CannotBeEmployedException);

        $startDate = Carbon::parse($startedAt)->toDayDateTimeString('minute') ?? now()->toDateTimeString('minute');

        $wrestler->employments()->updateOrCreate(['ended_at' => null], ['started_at' => $startDate]);
        $wrestler->updateStatusAndSave();
    }

    /**
     * Release a model.
     *
     * @param  \App\Models\Wrestler, $wrestler
     * @param  string|null $releasedAt
     * @return void
     */
    public function release($wrestler, $releasedAt = null)
    {
        throw_unless($wrestler->canBeReleased(), new CannotBeReleasedException);

        if ($wrestler->isSuspended()) {
            $this->reinstate($wrestler);
        }

        if ($wrestler->isInjured()) {
            $this->clearFromInjury($wrestler);
        }

        $releaseDate = Carbon::parse($releasedAt)->toDateTimeString('minute') ?? now()->toDateTimeString('minute');

        $wrestler->currentEmployment->update(['ended_at' => $releaseDate]);
        $wrestler->updateStatusAndSave();

        if ($wrestler->currentTagTeam) {
            $wrestler->currentTagTeam->updateStatusAndSave();
        }
    }

    /**
     * Injure a model.
     *
     * @param  \App\Models\Wrestler, $wrestler
     * @param  string|null $injuredAt
     * @return void
     */
    public function injure($wrestler, $injuredAt = null)
    {
        throw_unless($wrestler->canBeInjured(), new CannotBeInjuredException);

        $injuredDate = Carbon::parse($injuredAt)->toDateTimeString('minute') ?? now()->toDateTimeString('minute');

        $wrestler->injuries()->create(['started_at' => $injuredDate]);
        $wrestler->updateStatusAndSave();

        if ($wrestler->currentTagTeam) {
            $wrestler->currentTagTeam->updateStatusAndSave();
        }
    }

    /**
     * Clear a model from an injury.
     *
     * @param  \App\Models\Wrestler, $wrestler
     * @param  string|null $recoveredAt
     * @return void
     */
    public function clearFromInjury($wrestler, $recoveredAt = null)
    {
        throw_unless($wrestler->canBeClearedFromInjury(), new CannotBeClearedFromInjuryException('This entity could not be cleared from an injury.'));

        $recoveryDate = Carbon::parse($recoveredAt)->toDateTImeString('minute') ?? now()->toDateTimeString('minute');

        $wrestler->currentInjury()->update(['ended_at' => $recoveryDate]);
        $wrestler->updateStatusAndSave();

        if ($wrestler->currentTagTeam) {
            $wrestler->currentTagTeam->updateStatusAndSave();
        }
    }

    /**
     * Retire a model.
     *
     * @param  \App\Models\Wrestler, $wrestler
     * @param  string|null $retiredAt
     * @return void
     */
    public function retire($wrestler, $retiredAt = null)
    {
        throw_unless($wrestler->canBeRetired(), new CannotBeRetiredException);

        if ($wrestler->isSuspended()) {
            $this->reinstate($wrestler);
        }

        if ($wrestler->isInjured()) {
            $this->clearFromInjury($wrestler);
        }

        $retiredDate = Carbon::parse($retiredAt)->toDateTimeString('minute') ?: now()->toDateTimeString('minute');

        $wrestler->currentEmployment()->update(['ended_at' => $retiredDate]);
        $wrestler->retirements()->create(['started_at' => $retiredDate]);
        $wrestler->updateStatusAndSave();

        if ($wrestler->currentTagTeam) {
            $wrestler->currentTagTeam->updateStatusAndSave();
        }
    }

    /**
     * Unretire a model.
     *
     * @param  \App\Models\Wrestler, $wrestler
     * @param  string|null $unretiredAt
     * @return void
     */
    public function unretire($wrestler, $unretiredAt = null)
    {
        throw_unless($wrestler->canBeUnretired(), new CannotBeUnretiredException);

        $unretiredDate = Carbon::parse($unretiredAt)->toDateTimeString('minute') ?: now()->toDateTimeString('minute');

        $wrestler->currentRetirement()->update(['ended_at' => $unretiredDate]);
        $wrestler->employments()->create(['started_at' => $unretiredDate]);
        $wrestler->updateStatusAndSave();
    }

    /**
     * Suspend a model.
     *
     * @param  \App\Models\Wrestler, $wrestler
     * @param  string|null $suspendedAt
     * @return void
     */
    public function suspend($wrestler, $suspendedAt = null)
    {
        throw_unless($wrestler->canBeSuspended(), new CannotBeSuspendedException);

        $suspensionDate = Carbon::parse($suspendedAt)->toDateTimeString('minute') ?? now()->toDateTimeString('minute');

        $wrestler->suspensions()->create(['started_at' => $suspensionDate]);
        $wrestler->updateStatusAndSave();

        if ($wrestler->currentTagTeam) {
            $wrestler->currentTagTeam->updateStatusAndSave();
        }
    }

    /**
     * Reinstate a model.
     *
     * @param  \App\Models\Wrestler, $wrestler
     * @param  string|null $reinstatedAt
     * @return void
     */
    public function reinstate($wrestler, $reinstatedAt = null)
    {
        throw_unless($wrestler->canBeReinstated(), new CannotBeReinstatedException);

        $reinstatedDate = Carbon::parse($reinstatedAt)->toDateTimeString('minute') ?: now()->toDateTimeString('minute');

        $wrestler->currentSuspension()->update(['ended_at' => $reinstatedDate]);
        $wrestler->updateStatusAndSave();

        if ($wrestler->currentTagTeam) {
            $wrestler->currentTagTeam->updateStatusAndSave();
        }
    }
}

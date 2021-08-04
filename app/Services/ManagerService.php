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
use App\Models\Manager;
use App\Repositories\ManagerRepository;
use Carbon\Carbon;

class ManagerService
{
    protected $managerRepository;

    public function __construct(ManagerRepository $managerRepository)
    {
        $this->managerRepository = $managerRepository;
    }

    /**
     * Creates a new tag team.
     *
     * @param  array $data
     * @return \App\Models\Manager $manager
     */
    public function create(array $data): Manager
    {
        $manager = $this->managerRepository->create($data);

        if ($data['started_at']) {
            $this->employ($manager, $data['started_at']);
        }

        return $manager;
    }

    /**
     * Updates a new manager.
     *
     * @param  \App\Models\Manager $manager
     * @param  array $data
     * @return \App\Models\Manager $manager
     */
    public function update(Manager $manager, array $data): Manager
    {
        $manager->update([
            'name' => $data['name'],
            'height' => $data['height'],
            'weight' => $data['weight'],
            'hometown' => $data['hometown'],
            'signature_move' => $data['signature_move'],
        ]);

        if ($data['started_at']) {
            $this->employOrUpdateEmployment($manager, $data['started_at']);
        }

        return $manager;
    }

    public function employOrUpdateEmployment(Manager $manager, $startedAt)
    {
        if ($manager->isUnemployed()) {
            return $this->employ($manager, $startedAt);
        }

        if ($manager->hasFutureEmployment() && $manager->futureEmployment->started_at->ne($startedAt)) {
            return $manager->futureEmployment()->update(['started_at' => $startedAt]);
        }
    }

/**
     * Employ a model.
     *
     * @param  \App\Models\Manager, $manager
     * @param  string|null $startedAt
     * @return void
     */
    public function employ($manager, $startedAt = null)
    {
        throw_unless($manager->canBeEmployed(), new CannotBeEmployedException);

        $startDate = Carbon::parse($startedAt)->toDayDateTimeString('minute') ?? now()->toDateTimeString('minute');

        $manager->employments()->updateOrCreate(['ended_at' => null], ['started_at' => $startDate]);
        $manager->updateStatusAndSave();
    }

    /**
     * Release a model.
     *
     * @param  \App\Models\Manager, $manager
     * @param  string|null $releasedAt
     * @return void
     */
    public function release($manager, $releasedAt = null)
    {
        throw_unless($manager->canBeReleased(), new CannotBeReleasedException);

        if ($manager->isSuspended()) {
            $this->reinstate($manager);
        }

        if ($manager->isInjured()) {
            $this->clearFromInjury($manager);
        }

        $releaseDate = Carbon::parse($releasedAt)->toDateTimeString('minute') ?? now()->toDateTimeString('minute');

        $manager->currentEmployment->update(['ended_at' => $releaseDate]);
        $manager->updateStatusAndSave();

        if ($manager->currentTagTeam) {
            $manager->currentTagTeam->updateStatusAndSave();
        }
    }

    /**
     * Injure a model.
     *
     * @param  \App\Models\Manager, $manager
     * @param  string|null $injuredAt
     * @return void
     */
    public function injure($manager, $injuredAt = null)
    {
        throw_unless($manager->canBeInjured(), new CannotBeInjuredException);

        $injuredDate = Carbon::parse($injuredAt)->toDateTimeString('minute') ?? now()->toDateTimeString('minute');

        $manager->injuries()->create(['started_at' => $injuredDate]);
        $manager->updateStatusAndSave();

        if ($manager->currentTagTeam) {
            $manager->currentTagTeam->updateStatusAndSave();
        }
    }

    /**
     * Clear a model from an injury.
     *
     * @param  \App\Models\Manager, $manager
     * @param  string|null $recoveredAt
     * @return void
     */
    public function clearFromInjury($manager, $recoveredAt = null)
    {
        throw_unless($manager->canBeClearedFromInjury(), new CannotBeClearedFromInjuryException);

        $recoveryDate = Carbon::parse($recoveredAt)->toDateTImeString() ?? now()->toDateTimeString();

        $manager->currentInjury()->update(['ended_at' => $recoveryDate]);
        $manager->updateStatusAndSave();

        if ($manager->currentTagTeam) {
            $manager->currentTagTeam->updateStatusAndSave();
        }
    }

    /**
     * Retire a model.
     *
     * @param  \App\Models\Manager, $manager
     * @param  string|null $retiredAt
     * @return void
     */
    public function retire($manager, $retiredAt = null)
    {
        throw_unless($manager->canBeRetired(), new CannotBeRetiredException);

        if ($manager->isSuspended()) {
            $this->reinstate($manager);
        }

        if ($manager->isInjured()) {
            $this->clearFromInjury($manager);
        }

        $retiredDate = Carbon::parse($retiredAt)->toDateTimeString('minute') ?: now()->toDateTimeString('minute');

        $manager->currentEmployment()->update(['ended_at' => $retiredDate]);
        $manager->retirements()->create(['started_at' => $retiredDate]);
        $manager->updateStatusAndSave();

        if ($manager->currentTagTeam) {
            $manager->currentTagTeam->updateStatusAndSave();
        }
    }

    /**
     * Unretire a model.
     *
     * @param  \App\Models\Manager, $manager
     * @param  string|null $unretiredAt
     * @return void
     */
    public function unretire($manager, $unretiredAt = null)
    {
        throw_unless($manager->canBeUnretired(), new CannotBeUnretiredException);

        $unretiredDate = Carbon::parse($unretiredAt)->toDateTimeString('minute') ?: now()->toDateTimeString('minute');

        $manager->currentRetirement()->update(['ended_at' => $unretiredDate]);
        $manager->employments()->create(['started_at' => $unretiredDate]);
        $manager->updateStatusAndSave();
    }

    /**
     * Suspend a model.
     *
     * @param  \App\Models\Manager, $manager
     * @param  string|null $suspendedAt
     * @return void
     */
    public function suspend($manager, $suspendedAt = null)
    {
        throw_unless($manager->canBeSuspended(), new CannotBeSuspendedException);

        $suspensionDate = Carbon::parse($suspendedAt)->toDateTimeString('minute') ?? now()->toDateTimeString('minute');

        $manager->suspensions()->create(['started_at' => $suspensionDate]);
        $manager->updateStatusAndSave();

        if ($manager->currentTagTeam) {
            $manager->currentTagTeam->updateStatusAndSave();
        }
    }

    /**
     * Reinstate a model.
     *
     * @param  \App\Models\Manager, $manager
     * @param  string|null $reinstatedAt
     * @return void
     */
    public function reinstate($manager, $reinstatedAt = null)
    {
        throw_unless($manager->canBeReinstated(), new CannotBeReinstatedException);

        $reinstatedDate = Carbon::parse($reinstatedAt)->toDateTimeString('minute') ?: now()->toDateTimeString('minute');

        $manager->currentSuspension()->update(['ended_at' => $reinstatedDate]);
        $manager->updateStatusAndSave();

        if ($manager->currentTagTeam) {
            $manager->currentTagTeam->updateStatusAndSave();
        }
    }
}

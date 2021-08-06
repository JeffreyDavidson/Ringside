<?php

namespace App\Strategies\ClearInjury;

use App\Exceptions\CannotBeClearedFromInjuryException;
use App\Models\Contracts\Injurable;
use App\Repositories\WrestlerRepository;
use Carbon\Carbon;

class WrestlerClearInjuryStrategy extends BaseClearInjuryStrategy implements ClearInjuryStrategyInterface
{
    /**
     * The interface implementation.
     *
     * @var \App\Models\Contracts\Injurable
     */
    private Injurable $injurable;

    /**
     * The repository implementation.
     *
     * @var \App\Repositories\WrestlerRepository
     */
    private WrestlerRepository $wrestlerRepository;

    /**
     * Create a new wrestler clear injury strategy instance.
     *
     * @param \App\Models\Contracts\Injurable $injurable
     * @param \App\Repositories\WrestlerRepository $wrestlerRepository
     */
    public function __construct(Injurable $injurable, WrestlerRepository $wrestlerRepository)
    {
        $this->injurable = $injurable;
        $this->wrestlerRepository = $wrestlerRepository;
    }

    /**
     * Clear an injury of an injurable model.
     *
     * @param  string|null $recoveredAt
     * @return void
     */
    public function clearInjury(string $recoveredAt = null)
    {
        throw_unless($this->injurable->canBeClearedFromInjury(), new CannotBeClearedFromInjuryException);

        $recoveryDate = $recoveredAt ?? now()->toDateTimeString();

        $this->wrestlerRepository->clearInjury($this->injurable, $recoveryDate);
        $this->injurable->updateStatusAndSave();

        if ($this->injurable->currentTagTeam) {
            $this->injurable->currentTagTeam->updateStatusAndSave();
        }
    }

    /**
     * Updates a wrestler's status and saves.
     *
     * @return void
     */
    public function updateStatusAndSave()
    {
        $this->updateStatus();
        $this->save();
    }

    /**
     * Update the status for the wrestler.
     *
     * @return void
     */
    public function updateStatus()
    {
        if ($this->isCurrentlyEmployed()) {
            if ($this->isInjured()) {
                $this->status = WrestlerStatus::INJURED;
            } elseif ($this->isSuspended()) {
                $this->status = WrestlerStatus::SUSPENDED;
            } elseif ($this->isBookable()) {
                $this->status = WrestlerStatus::BOOKABLE;
            }
        } elseif ($this->hasFutureEmployment()) {
            $this->status = WrestlerStatus::FUTURE_EMPLOYMENT;
        } elseif ($this->isReleased()) {
            $this->status = WrestlerStatus::RELEASED;
        } elseif ($this->isRetired()) {
            $this->status = WrestlerStatus::RETIRED;
        } else {
            $this->status = WrestlerStatus::UNEMPLOYED;
        }
    }
}

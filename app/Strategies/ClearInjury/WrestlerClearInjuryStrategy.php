<?php

namespace App\Strategies\ClearInjury;

use App\Exceptions\CannotBeClearedFromInjuryException;
use App\Models\Contracts\Injurable;
use App\Repositories\WrestlerRepository;

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
     */
    public function __construct(Injurable $injurable)
    {
        $this->injurable = $injurable;
        $this->wrestlerRepository = new WrestlerRepository;
    }

    /**
     * Clear an injury of an injurable model.
     *
     * @param  string|null $recoveryDate
     * @return void
     */
    public function clearInjury(string $recoveryDate = null)
    {
        throw_unless($this->injurable->canBeClearedFromInjury(), new CannotBeClearedFromInjuryException);

        $recoveryDate = $recoveryDate ?? now()->toDateTimeString();

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

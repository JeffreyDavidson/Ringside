<?php

namespace App\Strategies\Unretire;

use App\Exceptions\CannotBeUnretiredException;
use App\Models\Contracts\Unretirable;
use Carbon\Carbon;

class TitleUnretireStrategy extends BaseUnretireStrategy implements UnretireStrategyInterface
{
    /**
     * The interface implementation.
     *
     * @var \App\Models\Contracts\Unretirable
     */
    private Unretirable $unretirable;

    /**
     * Create a new title unretire strategy instance.
     *
     * @param \App\Models\Contracts\Unretirable $unretirable
     */
    public function __construct(Unretirable $unretirable)
    {
        $this->unretirable = $unretirable;
    }

    /**
     * Unretire an unretirable model.
     *
     * @param  \Carbon\Carbon|null $unretiredAt
     * @return void
     */
    public function unretire(Carbon $unretiredAt = null)
    {
        throw_unless($this->unretirable->canBeUnretired(), new CannotBeUnretiredException);

        $unretiredDate = $unretiredAt ?: now();

        $this->unretirable->currentRetirement()->update(['ended_at' => $unretiredDate]);
        $this->unretirable->activate($unretiredDate);
        $this->unretirable->updateStatusAndSave();
    }
}

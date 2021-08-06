<?php

namespace App\Strategies\Unretire;

use App\Exceptions\CannotBeUnretiredException;
use App\Models\Contracts\Unretirable;

class ManagerUnretireStrategy extends BaseUnretireStrategy implements UnretireStrategyInterface
{
    /**
     * The interface implementation.
     *
     * @var \App\Models\Contracts\Unretirable
     */
    private Unretirable $unretirable;

    /**
     * Create a new manager unretire strategy instance.
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
     * @param  string|null $unretiredAt
     * @return void
     */
    public function unretire(string $unretiredAt = null)
    {
        throw_unless($this->unretirable->canBeUnretired(), new CannotBeUnretiredException);

        $unretiredDate = $unretiredAt ?: now()->toDateTimeString();

        $this->unretirable->currentRetirement()->update(['ended_at' => $unretiredDate]);
        $this->unretirable->employments()->create(['started_at' => $unretiredDate]);
        $this->unretirable->updateStatusAndSave();
    }
}

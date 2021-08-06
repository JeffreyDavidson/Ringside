<?php

namespace App\Strategies\Injure;

use App\Exceptions\CannotBeInjuredException;
use App\Models\Contracts\Injurable;

class WrestlerInjuryStrategy extends BaseInjuryStrategy implements InjuryStrategyInterface
{
    /**
     * The interface implementation.
     *
     * @var \App\Models\Contracts\Injurable
     */
    private Injurable $injurable;

    /**
     * Create a new wrestler injury strategy instance.
     *
     * @param \App\Models\Contracts\Injurable $injurable
     */
    public function __construct(Injurable $injurable)
    {
        $this->injurable = $injurable;
    }

    /**
     * Injure an injurable model.
     *
     * @param  string|null $injuredAt
     * @return void
     */
    public function injure(string $injuredAt = null)
    {
        throw_unless($this->injurable->canBeInjured(), new CannotBeInjuredException);

        $injuredDate = $injuredAt ?? now()->toDateTimeString();

        $this->injurable->injuries()->create(['started_at' => $injuredDate]);
        $this->injurable->updateStatusAndSave();

        if ($this->injurable->currentTagTeam) {
            $this->injurable->currentTagTeam->updateStatusAndSave();
        }
    }
}

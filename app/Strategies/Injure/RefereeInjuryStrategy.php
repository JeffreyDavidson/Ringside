<?php

namespace App\Strategies\Injure;

use App\Exceptions\CannotBeInjuredException;
use App\Models\Contracts\Injurable;
use Carbon\Carbon;

class RefereeInjuryStrategy extends BaseInjuryStrategy implements InjuryStrategyInterface
{
    private Injurable $injurable;

    public function __construct(Injurable $injurable)
    {
        $this->injurable = $injurable;
    }

    public function injure(Carbon $injuredAt = null)
    {
        throw_unless($this->injurable->canBeInjured(), new CannotBeInjuredException);

        $injuredDate = Carbon::parse($injuredAt)->toDateTimeString() ?? now()->toDateTimeString();

        $this->injurable->injuries()->create(['started_at' => $injuredDate]);
        $this->injurable->updateStatusAndSave();
    }
}

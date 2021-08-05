<?php

namespace App\Strategies\Unretire;

use App\Exceptions\CannotBeUnretiredException;
use App\Models\Contracts\Unretirable;
use Carbon\Carbon;

class TitleUnretireStrategy extends BaseUnretireStrategy implements UnretireStrategyInterface
{
    private Unretirable $unretirable;

    public function __construct(Unretirable $unretirable)
    {
        $this->unretirable = $unretirable;
    }

    public function unretire(Carbon $unretiredAt = null)
    {
        throw_unless($this->unretirable->canBeUnretired(), new CannotBeUnretiredException);

        $unretiredDate = $unretiredAt ?: now();

        $this->unretirable->currentRetirement()->update(['ended_at' => $unretiredDate]);
        $this->unretirable->activate($unretiredDate);
        $this->unretirable->updateStatusAndSave();
    }
}

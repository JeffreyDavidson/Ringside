<?php

namespace App\Strategies\Unretire;

use App\Exceptions\CannotBeUnretiredException;
use App\Models\Contracts\Unretirable;
use App\Strategies\ClearInjury\WrestlerClearInjuryStrategy;
use App\Strategies\Reinstate\WrestlerReinstateStrategy;
use Carbon\Carbon;

class WrestlerUnretireStrategy extends BaseUnretireStrategy
{
    private Unretirable $unretirable;

    public function __construct(Unretirable $unretirable)
    {
        $this->unretirable = $unretirable;
    }

    public function unretire(Carbon $retiredAt = null)
    {
        throw_unless($this->unretirable->canBeUnretired(), new CannotBeUnretiredException);

        $retiredDate = Carbon::parse($retiredAt)->toDateTimeString() ?: now()->toDateTimeString();

        $this->unretirable->currentEmployment()->update(['ended_at' => $retiredDate]);
        $this->unretirable->retirements()->create(['started_at' => $retiredDate]);
        $this->unretirable->updateStatusAndSave();

        if ($this->unretirable->currentTagTeam) {
            $this->unretirable->currentTagTeam->updateStatusAndSave();
        }
    }
}

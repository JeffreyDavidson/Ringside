<?php

declare(strict_types=1);

namespace App\Actions\Wrestlers;

use App\Models\Wrestlers\Wrestler;
use Illuminate\Support\Carbon;
use Lorisleiva\Actions\Concerns\AsAction;

/**
 * Clear injury action for wrestlers.
 *
 * This is an alias for HealAction to maintain backward compatibility with tests.
 * Use HealAction for new code.
 */
class ClearInjuryAction extends BaseWrestlerAction
{
    use AsAction;

    public function __construct(
        private HealAction $healAction
    ) {
        parent::__construct($this->healAction->wrestlerRepository);
    }

    /**
     * Clear injury from a wrestler.
     *
     * @param  Wrestler  $wrestler  The wrestler to heal
     * @param  Carbon|null  $recoveryDate  The recovery date (defaults to now)
     */
    public function handle(Wrestler $wrestler, ?Carbon $recoveryDate = null): void
    {
        $this->healAction->handle($wrestler, $recoveryDate);
    }
}

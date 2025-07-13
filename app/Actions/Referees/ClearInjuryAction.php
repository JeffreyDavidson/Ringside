<?php

declare(strict_types=1);

namespace App\Actions\Referees;

use App\Models\Referees\Referee;
use Illuminate\Support\Carbon;
use Lorisleiva\Actions\Concerns\AsAction;

/**
 * Clear injury action for referees.
 *
 * This is an alias for HealAction to maintain backward compatibility with tests.
 * Use HealAction for new code.
 */
class ClearInjuryAction extends BaseRefereeAction
{
    use AsAction;

    public function __construct(
        private HealAction $healAction
    ) {
        parent::__construct($this->healAction->refereeRepository);
    }

    /**
     * Clear injury from a referee.
     *
     * @param  Referee  $referee  The referee to heal
     * @param  Carbon|null  $recoveryDate  The recovery date (defaults to now)
     */
    public function handle(Referee $referee, ?Carbon $recoveryDate = null): void
    {
        $this->healAction->handle($referee, $recoveryDate);
    }
}

<?php

declare(strict_types=1);

namespace App\Actions\Wrestlers;

use App\Exceptions\Roster\CannotBeClearedFromInjuryException;
use App\Lifecycle\InjuryPeriodManager;
use App\Models\Wrestlers\Wrestler;
use App\Support\DateHelper;
use Illuminate\Support\Carbon;

class HealAction
{
    public function __construct(private readonly InjuryPeriodManager $injuryPeriods) {}

    /**
     * Heal a wrestler from injury.
     *
     * This handles the complete injury recovery workflow:
     * - Ends the injury period through the shared lifecycle component
     * - Potentially restores tag team bookability if all members are now available
     *
     * @throws CannotBeClearedFromInjuryException
     */
    public function handle(Wrestler $wrestler, ?Carbon $recoveryDate = null): void
    {
        $wrestler->ensureCanBeHealed();

        $recoveryDate = DateHelper::resolveDate($recoveryDate);

        $this->injuryPeriods->end($wrestler, $recoveryDate);

        // Note: Tag team bookability is handled automatically by the isBookable() method
        // which checks if all current wrestlers are available for competition
    }
}

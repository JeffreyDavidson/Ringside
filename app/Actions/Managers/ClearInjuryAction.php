<?php

declare(strict_types=1);

namespace App\Actions\Managers;

use App\Exceptions\Roster\CannotBeClearedFromInjuryException;
use App\Models\Managers\Manager;
use Illuminate\Support\Carbon;
use Lorisleiva\Actions\Concerns\AsAction;

class ClearInjuryAction
{
    use AsAction;

    /**
     * Clear an injury of a manager.
     *
     * @throws CannotBeClearedFromInjuryException
     */
    public function handle(Manager $manager, ?Carbon $recoveryDate = null): void
    {
        $this->ensureCanBeClearedFromInjury($manager);

        $recoveryDate ??= now();

        // Clear injury by ending current injury
        $currentInjury = $manager->currentInjury()->first();
        if ($currentInjury) {
            $currentInjury->update(['ended_at' => $recoveryDate->toDateTimeString()]);
        }
    }

    /**
     * Ensure a manager can be cleared from an injury.
     *
     * @throws CannotBeClearedFromInjuryException
     */
    private function ensureCanBeClearedFromInjury(Manager $manager): void
    {
        if (! $manager->isInjured()) {
            throw CannotBeClearedFromInjuryException::notInjured();
        }
    }
}

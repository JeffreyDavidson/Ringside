<?php

declare(strict_types=1);

namespace App\Actions\Wrestlers;

use App\Exceptions\Status\CannotBeClearedFromInjuryException;
use App\Models\Wrestlers\Wrestler;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Lorisleiva\Actions\Concerns\AsAction;

class HealAction
{
    use AsAction;

    /**
     * Heal a wrestler from injury.
     *
     * This handles the complete injury recovery workflow:
     * - Ends the injury record
     * - Potentially restores tag team bookability if all members are now available
     *
     * @throws CannotBeClearedFromInjuryException
     */
    public function handle(Wrestler $wrestler, ?Carbon $recoveryDate = null): void
    {
        $wrestler->ensureCanBeHealed();

        $recoveryDate = $recoveryDate ?? now();

        DB::transaction(function () use ($wrestler, $recoveryDate): void {
            $currentInjury = $wrestler->currentInjury()->first();

            if ($currentInjury) {
                $currentInjury->update(['ended_at' => $recoveryDate->toDateTimeString()]);
            }

            // Note: Tag team bookability is handled automatically by the isBookable() method
            // which checks if all current wrestlers are available for competition
        });
    }
}

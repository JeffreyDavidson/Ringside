<?php

declare(strict_types=1);

namespace App\Actions\Wrestlers;

use App\Actions\Concerns\StatusTransitionPipeline;
use App\Exceptions\Roster\CannotBeClearedFromInjuryException;
use App\Models\Wrestlers\Wrestler;
use App\Support\DateHelper;
use Illuminate\Support\Carbon;
use Lorisleiva\Actions\Concerns\AsAction;

class HealAction
{
    use AsAction;

    /**
     * Heal a wrestler from injury.
     *
     * This handles the complete injury recovery workflow:
     * - Uses StatusTransitionPipeline for consistent injury ending
     * - Potentially restores tag team bookability if all members are now available
     *
     * ARCHITECTURAL PATTERN:
     * Uses StatusTransitionPipeline for consistent status handling, following the same
     * pattern as other wrestler actions.
     *
     * @throws CannotBeClearedFromInjuryException
     */
    public function handle(Wrestler $wrestler, ?Carbon $recoveryDate = null): void
    {
        $wrestler->ensureCanBeHealed();

        $recoveryDate = DateHelper::resolveDate($recoveryDate);

        // Use StatusTransitionPipeline for consistent injury healing
        StatusTransitionPipeline::heal($wrestler, $recoveryDate)->execute();

        // Note: Tag team bookability is handled automatically by the isBookable() method
        // which checks if all current wrestlers are available for competition
    }
}

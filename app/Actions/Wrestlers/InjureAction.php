<?php

declare(strict_types=1);

namespace App\Actions\Wrestlers;

use App\Actions\Concerns\StatusTransitionPipeline;
use App\Exceptions\Roster\CannotBeInjuredException;
use App\Models\Wrestlers\Wrestler;
use App\Support\DateHelper;
use Illuminate\Support\Carbon;
use Lorisleiva\Actions\Concerns\AsAction;

class InjureAction
{
    use AsAction;

    /**
     * Injure a wrestler and make them unavailable for competition.
     *
     * This handles the complete wrestler injury workflow using StatusTransitionPipeline:
     * - Validates the wrestler can be injured through pipeline validation
     * - Uses StatusTransitionPipeline to properly create injury record
     * - Maintains transaction boundaries and error handling through pipeline
     * - Makes the wrestler unavailable for match bookings
     * - May affect tag team bookability if wrestler is in a team
     *
     * ARCHITECTURAL PATTERN:
     * Uses StatusTransitionPipeline for consistency with other entity injury operations
     * and proper status transition management.
     *
     * @param  Wrestler  $wrestler  The wrestler to injure
     * @param  Carbon|null  $injuryDate  The injury start date (defaults to now)
     * @throws CannotBeInjuredException When wrestler cannot be injured due to business rules
     *
     * @example
     * ```php
     * // Injure wrestler immediately
     * InjureAction::run($wrestler);
     *
     * // Injure with specific start date
     * InjureAction::run($wrestler, Carbon::parse('2024-01-15'));
     * ```
     */
    public function handle(Wrestler $wrestler, ?Carbon $injuryDate = null): void
    {
        $wrestler->ensureCanBeInjured();

        $injuryDate = DateHelper::resolveDate($injuryDate);

        StatusTransitionPipeline::injure($wrestler, $injuryDate)->execute();
    }
}

<?php

declare(strict_types=1);

namespace App\Actions\Wrestlers;

use App\Exceptions\Status\CannotBeInjuredException;
use App\Models\Wrestlers\Wrestler;
use Illuminate\Support\Carbon;
use Lorisleiva\Actions\Concerns\AsAction;

class InjureAction extends BaseWrestlerAction
{
    use AsAction;

    /**
     * Injure a wrestler and make them unavailable for competition.
     *
     * This handles the complete wrestler injury workflow:
     * - Validates the wrestler can be injured (currently employed)
     * - Creates an injury record with the specified start date
     * - Makes the wrestler unavailable for match bookings
     * - May affect tag team bookability if wrestler is in a team
     *
     * @param  Wrestler  $wrestler  The wrestler to injure
     * @param  Carbon|null  $injuryDate  The injury start date (defaults to now)
     *
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
        // Validate business rules before proceeding
        $wrestler->ensureCanBeInjured();

        $injuryDate = $this->getEffectiveDate($injuryDate);

        $this->wrestlerRepository->createInjury($wrestler, $injuryDate);
    }
}

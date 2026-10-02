<?php

declare(strict_types=1);

namespace App\Builders\Roster;

use App\Builders\Concerns\FiltersByEmploymentStatus;
use App\Builders\Concerns\FiltersByRetirementStatus;
use App\Builders\Concerns\LoadsFirstEmployment;
use App\Builders\Concerns\OrdersByKeyForLocking;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * @template TModel of Model
 *
 * @extends Builder<TModel>
 */
abstract class IndividualBuilder extends Builder
{
    use FiltersByEmploymentStatus;
    use FiltersByRetirementStatus;
    use LoadsFirstEmployment;
    use OrdersByKeyForLocking;

    /** Relationship existence projections read by isInjured() and isSuspended(). */
    public const array AVAILABILITY_STATE = [
        'currentInjury as availability_current_injury_exists',
        'currentSuspension as availability_current_suspension_exists',
    ];

    /**
     * Project the current injury and suspension state so availability badges
     * render without per-row queries.
     */
    public function withAvailabilityState(): static
    {
        return $this->withExists(self::AVAILABILITY_STATE);
    }
}

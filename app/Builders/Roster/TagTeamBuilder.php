<?php

declare(strict_types=1);

namespace App\Builders\Roster;

use App\Builders\Concerns\FiltersByEmploymentStatus;
use App\Builders\Concerns\FiltersByName;
use App\Builders\Concerns\FiltersByRetirementStatus;
use App\Builders\Concerns\LoadsFirstEmployment;
use App\Builders\Concerns\OrdersByKeyForLocking;
use App\Models\Roster\TagTeams\TagTeam;
use Illuminate\Database\Eloquent\Builder;

/**
 * @template TModel of TagTeam
 *
 * @extends Builder<TModel>
 */
class TagTeamBuilder extends Builder
{
    use FiltersByEmploymentStatus;
    use FiltersByName;
    use FiltersByRetirementStatus;
    use LoadsFirstEmployment;
    use OrdersByKeyForLocking;

    /** Relationship existence projections read by isSuspended(). */
    public const array AVAILABILITY_STATE = ['currentSuspension as availability_current_suspension_exists'];
}

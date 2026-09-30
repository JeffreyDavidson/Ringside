<?php

declare(strict_types=1);

namespace App\Builders\Roster;

use App\Builders\Concerns\FiltersByEmploymentStatus;
use App\Builders\Concerns\FiltersByName;
use App\Builders\Concerns\FiltersByRetirementStatus;
use App\Builders\Concerns\LoadsFirstEmployment;
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

    /**
     * Project the current suspension state so availability badges render
     * without per-row queries.
     */
    public function withAvailabilityState(): static
    {
        return $this->withExists('currentSuspension as availability_current_suspension_exists');
    }
}

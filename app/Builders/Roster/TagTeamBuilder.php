<?php

declare(strict_types=1);

namespace App\Builders\Roster;

use App\Builders\Concerns\FiltersByEmploymentStatus;
use App\Builders\Concerns\FiltersByName;
use App\Builders\Concerns\FiltersByRetirementStatus;
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
}

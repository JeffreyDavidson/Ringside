<?php

declare(strict_types=1);

namespace App\Builders\Roster;

use App\Builders\Concerns\HasEmploymentScopes;
use App\Builders\Concerns\HasRetirementScopes;
use App\Models\TagTeams\TagTeam;
use Illuminate\Database\Eloquent\Builder;

/**
 * @template TModel of TagTeam
 *
 * @extends Builder<TModel>
 */
class TagTeamBuilder extends Builder
{
    use HasEmploymentScopes;
    use HasRetirementScopes;
}

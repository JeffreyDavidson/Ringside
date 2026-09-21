<?php

declare(strict_types=1);

namespace App\Builders\Roster;

use App\Builders\Concerns\HasNameSearch;
use App\Models\Roster\Referees\Referee;

/**
 * @template TModel of Referee
 *
 * @extends IndividualBuilder<TModel>
 */
class RefereeBuilder extends IndividualBuilder
{
    use HasNameSearch;
}

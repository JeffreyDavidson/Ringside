<?php

declare(strict_types=1);

namespace App\Builders\Roster;

use App\Builders\Concerns\HasNameSearch;
use App\Models\Roster\Managers\Manager;

/**
 * @template TModel of Manager
 *
 * @extends IndividualBuilder<TModel>
 */
class ManagerBuilder extends IndividualBuilder
{
    use HasNameSearch;
}

<?php

declare(strict_types=1);

namespace App\Builders\Roster;

use App\Models\Roster\Wrestlers\Wrestler;

/**
 * @template TModel of Wrestler
 *
 * @extends IndividualBuilder<TModel>
 */
class WrestlerBuilder extends IndividualBuilder {}

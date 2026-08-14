<?php

declare(strict_types=1);

namespace App\Builders\Roster;

use App\Builders\Concerns\HasEmploymentScopes;
use App\Builders\Concerns\HasRetirementScopes;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * @template TModel of Model
 *
 * @extends Builder<TModel>
 */
abstract class IndividualBuilder extends Builder
{
    use HasEmploymentScopes;
    use HasRetirementScopes;
}

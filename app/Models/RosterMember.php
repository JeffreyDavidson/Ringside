<?php

declare(strict_types=1);

namespace App\Models;

use App\Builders\RosterMemberQueryBuilder;
use App\Models\Concerns\HasEmployments;
use App\Models\Concerns\HasRetirements;
use App\Models\Concerns\HasSuspensions;
use App\Models\Contracts\Employable;
use Illuminate\Database\Eloquent\Model;

abstract class RosterMember extends Model implements Employable
{
    use HasEmployments;
    use HasRetirements;
    use HasSuspensions;

    /**
     * Create a new Eloquent query builder for the model.
     *
     * @param  \Illuminate\Database\Query\Builder  $query
     * @return \App\Builders\RosterMemberQueryBuilder
     */
    public function newEloquentBuilder($query)
    {
        return new RosterMemberQueryBuilder($query);
    }
}

<?php

declare(strict_types=1);

namespace App\Models;

use App\Builders\RosterMemberQueryBuilder;
use App\Models\Contracts\Employable;
use App\Models\Contracts\Retirable;
use App\Models\Contracts\Suspendable;
use Illuminate\Database\Eloquent\Model;

abstract class RosterMember extends Model implements Employable, Retirable, Suspendable
{
    use Concerns\HasEmployments;
    use Concerns\HasRetirements;
    use Concerns\HasSuspensions;

    /**
     * Create a new Eloquent query builder for the model.
     */
    public function newEloquentBuilder($query): RosterMemberQueryBuilder
    {
        return new RosterMemberQueryBuilder($query);
    }
}

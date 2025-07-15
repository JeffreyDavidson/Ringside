<?php

declare(strict_types=1);

namespace App\Models\Contracts;

use App\Models\Matches\EventMatch;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;

/**
 * @template TMatchCompetitor of Model
 */
interface Bookable
{
    /**
     * Retrieve the matches this entity is involved in.
     *
     * @return Relation<EventMatch>
     */
    public function matches(): Relation;

    /**
     * @return Relation<EventMatch>
     */
    public function previousMatches(): Relation;

    /**
     * Determine if the competitor is eligible to be booked.
     */
    public function isBookable(): bool;
}

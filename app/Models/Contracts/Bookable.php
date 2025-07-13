<?php

declare(strict_types=1);

namespace App\Models\Contracts;

use App\Models\Matches\EventMatch;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphToMany;

/**
 * @template TMatchCompetitor of Model
 */
interface Bookable
{
    /**
     * Retrieve the matches this competitor is involved in.
     *
     * @return MorphToMany<EventMatch, Model>
     */
    public function matches(): MorphToMany;

    /**
     * @return MorphToMany<EventMatch, Model>
     */
    public function previousMatches(): MorphToMany;

    /**
     * Determine if the competitor is eligible to be booked.
     */
    public function isBookable(): bool;
}

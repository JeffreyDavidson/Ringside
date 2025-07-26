<?php

declare(strict_types=1);

namespace App\Models\Contracts;

use App\Models\Matches\EventMatch;
use App\Models\Matches\MatchCompetitor;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphToMany;

/**
 * Contract for models that can be booked as competitors in matches.
 *
 * This interface is for entities like Wrestlers and TagTeams that participate
 * in matches as competitors through polymorphic many-to-many relationships.
 *
 * @template TDeclaringModel of Model
 */
interface BookableCompetitor extends Bookable
{
    /**
     * Retrieve the matches this competitor has participated in.
     *
     * @return MorphToMany<EventMatch, $this, MatchCompetitor>
     */
    public function matches(): MorphToMany;

    /**
     * Retrieve previous matches this competitor has participated in.
     *
     * @return MorphToMany<EventMatch, $this, MatchCompetitor>
     */
    public function previousMatches(): MorphToMany;
}

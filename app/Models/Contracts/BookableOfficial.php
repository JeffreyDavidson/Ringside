<?php

declare(strict_types=1);

namespace App\Models\Contracts;

use App\Models\Matches\EventMatch;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * Contract for models that can be booked as officials in matches.
 *
 * This interface is for entities like Referees that officiate matches
 * through traditional many-to-many relationships.
 *
 * @template TDeclaringModel of Model
 */
interface BookableOfficial extends Bookable
{
    /**
     * Retrieve the matches this official has officiated.
     *
     * @return BelongsToMany<EventMatch, $this>
     */
    public function matches(): BelongsToMany;

    /**
     * Retrieve previous matches this official has officiated.
     *
     * @return BelongsToMany<EventMatch, $this>
     */
    public function previousMatches(): BelongsToMany;
}

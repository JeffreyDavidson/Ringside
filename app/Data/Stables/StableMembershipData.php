<?php

declare(strict_types=1);

namespace App\Data\Stables;

use App\Models\TagTeams\TagTeam;
use App\Models\Wrestlers\Wrestler;
use Illuminate\Database\Eloquent\Collection;

/**
 * Data object for stable membership information.
 *
 * Encapsulates the wrestlers and tag teams that belong to a stable,
 * providing type safety and clear structure for member collections.
 */
readonly class StableMembershipData
{
    /**
     * Create a new stable membership data instance.
     *
     * @param  Collection<int, Wrestler>|null  $wrestlers  The wrestlers in the stable
     * @param  Collection<int, TagTeam>|null  $tagTeams  The tag teams in the stable
     */
    public function __construct(
        public ?Collection $wrestlers = null,
        public ?Collection $tagTeams = null,
    ) {}
}

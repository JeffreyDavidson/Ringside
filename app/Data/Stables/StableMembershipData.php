<?php

declare(strict_types=1);

namespace App\Data\Stables;

use App\Models\Roster\TagTeams\TagTeam;
use App\Models\Roster\Wrestlers\Wrestler;
use Illuminate\Support\Collection;

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
     * @param  Collection<int, Wrestler>  $wrestlers  The wrestlers in the stable; empty when none
     * @param  Collection<int, TagTeam>  $tagTeams  The tag teams in the stable; empty when none
     */
    public function __construct(
        public Collection $wrestlers = new Collection,
        public Collection $tagTeams = new Collection,
    ) {}

    /**
     * Check if there are no members at all.
     */
    public function isEmpty(): bool
    {
        return $this->wrestlers->isEmpty() && $this->tagTeams->isEmpty();
    }

    /**
     * Check if there are any members.
     */
    public function isNotEmpty(): bool
    {
        return ! $this->isEmpty();
    }

    /**
     * Get the stable headcount, counting wrestlers as one and tag teams as two.
     */
    public function getTotalMemberCount(): int
    {
        return $this->wrestlers->count() + ($this->tagTeams->count() * 2);
    }
}

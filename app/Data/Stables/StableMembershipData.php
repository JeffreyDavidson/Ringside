<?php

declare(strict_types=1);

namespace App\Data\Stables;

use App\Models\TagTeams\TagTeam;
use App\Models\Wrestlers\Wrestler;
use Illuminate\Support\Collection;

/**
 * Data object for stable membership information.
 *
 * Encapsulates the wrestlers and tag teams that belong to a stable,
 * providing type safety and clear structure for member collections.
 */
readonly class StableMembershipData
{
    public const int MINIMUM_MEMBER_COUNT = 3;

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

    /**
     * Check if there are no members at all.
     */
    public function isEmpty(): bool
    {
        return ($this->wrestlers === null || $this->wrestlers->isEmpty()) &&
               ($this->tagTeams === null || $this->tagTeams->isEmpty());
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
        $wrestlerCount = $this->wrestlers?->count() ?? 0;
        $tagTeamCount = $this->tagTeams?->count() ?? 0;

        return $wrestlerCount + ($tagTeamCount * 2);
    }

    public function hasMinimumMembers(): bool
    {
        return $this->getTotalMemberCount() >= self::MINIMUM_MEMBER_COUNT;
    }
}

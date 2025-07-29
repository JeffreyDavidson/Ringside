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
     * Filter members to only include employed/available ones.
     */
    public function filterEmployedMembers(): self
    {
        $employedWrestlers = $this->wrestlers?->filter(fn (Wrestler $wrestler) => $wrestler->isEmployed());
        $employedTagTeams = $this->tagTeams?->filter(fn (TagTeam $tagTeam) => $tagTeam->isEmployed());

        return new self(
            wrestlers: $employedWrestlers?->isNotEmpty() ? $employedWrestlers : null,
            tagTeams: $employedTagTeams?->isNotEmpty() ? $employedTagTeams : null
        );
    }

    /**
     * Get wrestlers that need to be retired (not already retired).
     */
    public function getWrestlersToRetire(): ?Collection
    {
        return $this->wrestlers?->filter(fn (Wrestler $wrestler) => ! $wrestler->isRetired());
    }

    /**
     * Get tag teams that need to be retired (not already retired).
     */
    public function getTagTeamsToRetire(): ?Collection
    {
        return $this->tagTeams?->filter(fn (TagTeam $tagTeam) => ! $tagTeam->isRetired());
    }

    /**
     * Get wrestlers that can be unretired (currently retired).
     */
    public function getWrestlersToUnretire(): ?Collection
    {
        return $this->wrestlers?->filter(fn (Wrestler $wrestler) => $wrestler->isRetired());
    }

    /**
     * Get tag teams that can be unretired (currently retired).
     */
    public function getTagTeamsToUnretire(): ?Collection
    {
        return $this->tagTeams?->filter(fn (TagTeam $tagTeam) => $tagTeam->isRetired());
    }

    /**
     * Get total count of all members.
     */
    public function getTotalMemberCount(): int
    {
        $wrestlerCount = $this->wrestlers?->count() ?? 0;
        $tagTeamCount = $this->tagTeams?->count() ?? 0;

        return $wrestlerCount + $tagTeamCount;
    }

    /**
     * Check if membership contains any wrestlers.
     */
    public function hasWrestlers(): bool
    {
        return $this->wrestlers !== null && $this->wrestlers->isNotEmpty();
    }

    /**
     * Check if membership contains any tag teams.
     */
    public function hasTagTeams(): bool
    {
        return $this->tagTeams !== null && $this->tagTeams->isNotEmpty();
    }
}

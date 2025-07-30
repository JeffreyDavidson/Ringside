<?php

declare(strict_types=1);

namespace App\Data\TagTeams;

use App\Models\Managers\Manager;
use App\Models\Wrestlers\Wrestler;
use Illuminate\Database\Eloquent\Collection;

/**
 * Data object for tag team membership information.
 *
 * Encapsulates the wrestlers and managers that belong to a tag team,
 * providing type safety and clear structure for member collections.
 */
readonly class TagTeamMembershipData
{
    /**
     * Create a new tag team membership data instance.
     *
     * @param  Collection<int, Wrestler>|null  $wrestlers  The wrestlers in the tag team
     * @param  Collection<int, Manager>|null  $managers  The managers of the tag team
     */
    public function __construct(
        public ?Collection $wrestlers = null,
        public ?Collection $managers = null,
    ) {}

    /**
     * Create membership data from individual wrestler properties.
     */
    public static function fromWrestlers(?Wrestler $wrestlerA, ?Wrestler $wrestlerB, ?Collection $managers = null): self
    {
        $wrestlers = new Collection(array_filter([$wrestlerA, $wrestlerB]));

        return new self(
            wrestlers: $wrestlers->isNotEmpty() ? $wrestlers : null,
            managers: $managers
        );
    }

    /**
     * Check if there are no members at all.
     */
    public function isEmpty(): bool
    {
        return ($this->wrestlers === null || $this->wrestlers->isEmpty()) &&
               ($this->managers === null || $this->managers->isEmpty());
    }

    /**
     * Check if there are any members.
     */
    public function isNotEmpty(): bool
    {
        return ! $this->isEmpty();
    }

    /**
     * Get the wrestlers collection, defaulting to empty Eloquent collection.
     */
    public function getWrestlers(): Collection
    {
        return $this->wrestlers ?? new Collection();
    }

    /**
     * Get the managers collection, defaulting to empty Eloquent collection.
     */
    public function getManagers(): Collection
    {
        return $this->managers ?? new Collection();
    }

    /**
     * Check if membership contains any wrestlers.
     */
    public function hasWrestlers(): bool
    {
        return $this->wrestlers !== null && $this->wrestlers->isNotEmpty();
    }

    /**
     * Check if membership contains any managers.
     */
    public function hasManagers(): bool
    {
        return $this->managers !== null && $this->managers->isNotEmpty();
    }

    /**
     * Get total count of all members.
     */
    public function getTotalMemberCount(): int
    {
        $wrestlerCount = $this->wrestlers?->count() ?? 0;
        $managerCount = $this->managers?->count() ?? 0;

        return $wrestlerCount + $managerCount;
    }
}

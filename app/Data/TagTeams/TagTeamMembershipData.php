<?php

declare(strict_types=1);

namespace App\Data\TagTeams;

use App\Models\Roster\Managers\Manager;
use App\Models\Roster\Wrestlers\Wrestler;
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
     *
     * @param  Collection<int, Manager>|null  $managers
     */
    public static function fromWrestlers(Wrestler $wrestlerA, Wrestler $wrestlerB, ?Collection $managers = null): self
    {
        return new self(
            wrestlers: new Collection([$wrestlerA, $wrestlerB]),
            managers: $managers
        );
    }

    /**
     * Get the wrestlers collection, defaulting to empty Eloquent collection.
     *
     * @return Collection<int, Wrestler>
     */
    public function getWrestlers(): Collection
    {
        return $this->wrestlers ?? new Collection;
    }

    /**
     * Get the managers collection, defaulting to empty Eloquent collection.
     *
     * @return Collection<int, Manager>
     */
    public function getManagers(): Collection
    {
        return $this->managers ?? new Collection;
    }
}

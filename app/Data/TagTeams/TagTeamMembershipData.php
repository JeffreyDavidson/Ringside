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
     * @param  Collection<int, Wrestler>  $wrestlers  The wrestlers in the tag team
     * @param  Collection<int, Manager>|null  $managers  The managers of the tag team; null leaves the managers alone
     */
    public function __construct(
        public Collection $wrestlers = new Collection,
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
            managers: $managers,
        );
    }
}

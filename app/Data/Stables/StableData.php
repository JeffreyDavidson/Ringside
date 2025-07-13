<?php

declare(strict_types=1);

namespace App\Data\Stables;

use App\Models\Managers\Manager;
use App\Models\TagTeams\TagTeam;
use App\Models\Wrestlers\Wrestler;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;

readonly class StableData
{
    /**
     * Create a new stable data instance.
     *
     * @param  Collection<int, TagTeam>  $tagTeams
     * @param  Collection<int, Wrestler>  $wrestlers
     * @param  Collection<int, Manager>  $managers
     */
    public function __construct(
        public string $name,
        public ?Carbon $start_date,
        public Collection $tagTeams,
        public Collection $wrestlers,
        public Collection $managers,
    ) {}
}

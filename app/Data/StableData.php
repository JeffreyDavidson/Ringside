<?php

declare(strict_types=1);

namespace App\Data;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;

readonly class StableData
{
    /**
     * Create a new stable data instance.
     *
     * @param  Collection<int, \App\Models\TagTeam>  $tagTeams
     * @param  Collection<int, \App\Models\Wrestler>  $wrestlers
     * @param  Collection<int, \App\Models\Manager>  $managers
     */
    public function __construct(
        public string $name,
        public ?Carbon $start_date,
        public Collection $tagTeams,
        public Collection $wrestlers,
        public Collection $managers,
    ) {}
}

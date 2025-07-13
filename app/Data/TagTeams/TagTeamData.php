<?php

declare(strict_types=1);

namespace App\Data\TagTeams;

use App\Models\Managers\Manager;
use App\Models\Wrestlers\Wrestler;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

readonly class TagTeamData
{
    /**
     * Create a new tag team data instance.
     *
     * @param  Collection<int, Manager>|null  $managers
     */
    public function __construct(
        public string $name,
        public ?string $signature_move,
        public ?Carbon $employment_date,
        public ?Wrestler $wrestlerA,
        public ?Wrestler $wrestlerB,
        public ?Collection $managers = null,
    ) {}
}

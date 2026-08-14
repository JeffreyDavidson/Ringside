<?php

declare(strict_types=1);

namespace App\Builders\Roster;

use App\Models\TagTeams\TagTeamManager;
use App\Models\Wrestlers\WrestlerManager;
use Illuminate\Database\Eloquent\Builder;

/**
 * @template TModel of WrestlerManager|TagTeamManager
 *
 * @extends Builder<TModel>
 */
class ManagerAssignmentBuilder extends Builder
{
    public function forManagerId(int $managerId): static
    {
        $this->where('manager_id', $managerId);

        return $this;
    }

    public function current(): static
    {
        $this->whereNull('fired_at');

        return $this;
    }

    public function ended(): static
    {
        $this->whereNotNull('fired_at');

        return $this;
    }
}

<?php

declare(strict_types=1);

namespace App\Actions\TagTeams;

use App\Actions\Managers\EndManagerAssignmentsAction;
use App\Models\Roster\TagTeams\TagTeam;
use Illuminate\Support\Carbon;

class EndMembershipsAction
{
    public function __construct(
        protected EndManagerAssignmentsAction $endManagerAssignmentsAction,
    ) {}

    public function handle(TagTeam $tagTeam, Carbon $date): void
    {
        $tagTeam->wrestlers()->newPivotQuery()
            ->whereNull('left_at')
            ->update(['left_at' => $date]);
        $this->endManagerAssignmentsAction->handle($tagTeam, $date);
    }
}

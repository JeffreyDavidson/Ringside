<?php

declare(strict_types=1);

namespace App\Actions\Managers;

use App\Models\Managers\Manager;
use Illuminate\Support\Carbon;
use Lorisleiva\Actions\Concerns\AsAction;

class RemoveFromCurrentTagTeamsAction extends BaseManagerAction
{
    use AsAction;

    public function handle(Manager $manager, ?Carbon $removalDate = null): void
    {
        $removalDate = $removalDate ?? now();
        
        $manager->tagTeams()->wherePivotNull('fired_at')->updateExistingPivot(
            $manager->tagTeams()->wherePivotNull('fired_at')->pluck('tag_team_id'),
            ['fired_at' => $removalDate]
        );
    }
}

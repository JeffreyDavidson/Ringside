<?php

declare(strict_types=1);

namespace App\Actions\TagTeams;

use App\Actions\Managers\AssignManagersAction;
use App\Data\TagTeams\TagTeamMembershipData;
use App\Models\Roster\TagTeams\TagTeam;
use App\Models\Roster\Wrestlers\Wrestler;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;

class EstablishMembershipAction
{
    public function __construct(
        protected AssignManagersAction $assignManagersAction,
    ) {}

    public function handle(TagTeam $tagTeam, TagTeamMembershipData $members, Carbon $date): void
    {
        if ($members->wrestlers instanceof Collection && $members->wrestlers->isNotEmpty()) {
            $tagTeam->wrestlers()->attach($members->wrestlers->map(
                fn (Wrestler $wrestler): int => Arr::integer(['key' => $wrestler->getKey()], 'key'),
            )->all(), [
                'joined_at' => $date,
                'left_at' => null,
            ]);
        }

        $this->assignManagersAction->handle($tagTeam, $members->managers, $date);
    }
}

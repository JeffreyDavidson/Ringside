<?php

declare(strict_types=1);

namespace App\Actions\TagTeams;

use App\Models\Roster\TagTeams\TagTeam;
use App\Models\Roster\Wrestlers\Wrestler;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;

class RejoinMembersAction
{
    public function __construct(
        private readonly LockIncomingWrestlersAction $lockIncomingWrestlers,
    ) {}

    /**
     * Start a new membership for each wrestler who left the tag team and is not currently a member.
     *
     * Must run inside the caller's transaction with the tag team locked.
     *
     * @param  Collection<int, Wrestler>  $wrestlers
     */
    public function handle(TagTeam $tagTeam, Collection $wrestlers, Carbon $date): void
    {
        $currentMemberIds = $tagTeam->currentWrestlers()->pluck('wrestlers.id');
        $leavers = $wrestlers->reject(fn (Wrestler $wrestler): bool => $currentMemberIds->contains($wrestler->id));

        if ($leavers->isEmpty()) {
            return;
        }

        $this->lockIncomingWrestlers->handle($tagTeam, $leavers);

        $tagTeam->wrestlers()->attach($leavers->modelKeys(), ['joined_at' => $date, 'left_at' => null]);
    }
}

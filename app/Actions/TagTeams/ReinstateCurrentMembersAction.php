<?php

declare(strict_types=1);

namespace App\Actions\TagTeams;

use App\Actions\Managers\ReinstateAction as ReinstateManagerAction;
use App\Actions\Wrestlers\ReinstateAction as ReinstateWrestlerAction;
use App\Models\Roster\Managers\Manager;
use App\Models\Roster\TagTeams\TagTeam;
use App\Models\Roster\Wrestlers\Wrestler;
use Illuminate\Support\Carbon;

class ReinstateCurrentMembersAction
{
    public function __construct(
        private readonly ReinstateWrestlerAction $reinstateWrestler,
        private readonly ReinstateManagerAction $reinstateManager,
    ) {}

    public function handle(TagTeam $tagTeam, Carbon $reinstatementDate): void
    {
        $wrestlers = $tagTeam->currentWrestlers()
            ->get()
            ->filter(fn (Wrestler $wrestler): bool => $wrestler->isSuspended());

        foreach ($wrestlers as $wrestler) {
            $this->reinstateWrestler->handle($wrestler, $reinstatementDate);
        }

        $managers = $tagTeam->currentManagers()
            ->get()
            ->filter(fn (Manager $manager): bool => $manager->isSuspended());

        foreach ($managers as $manager) {
            $this->reinstateManager->handle($manager, $reinstatementDate);
        }
    }
}

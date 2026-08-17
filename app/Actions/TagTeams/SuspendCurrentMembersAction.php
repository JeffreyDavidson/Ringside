<?php

declare(strict_types=1);

namespace App\Actions\TagTeams;

use App\Actions\Managers\SuspendAction as SuspendManagerAction;
use App\Actions\Wrestlers\SuspendAction as SuspendWrestlerAction;
use App\Models\Roster\Managers\Manager;
use App\Models\Roster\TagTeams\TagTeam;
use App\Models\Roster\Wrestlers\Wrestler;
use Illuminate\Support\Carbon;

class SuspendCurrentMembersAction
{
    public function __construct(
        private readonly SuspendWrestlerAction $suspendWrestler,
        private readonly SuspendManagerAction $suspendManager,
    ) {}

    public function handle(TagTeam $tagTeam, Carbon $suspensionDate): void
    {
        $wrestlers = $tagTeam->currentWrestlers()
            ->get()
            ->filter(fn (Wrestler $wrestler): bool => $wrestler->isEmployed() && ! $wrestler->isSuspended());

        foreach ($wrestlers as $wrestler) {
            $this->suspendWrestler->handle($wrestler, $suspensionDate);
        }

        $managers = $tagTeam->currentManagers()
            ->get()
            ->filter(fn (Manager $manager): bool => $manager->isEmployed() && ! $manager->isSuspended());

        foreach ($managers as $manager) {
            $this->suspendManager->handle($manager, $suspensionDate);
        }
    }
}

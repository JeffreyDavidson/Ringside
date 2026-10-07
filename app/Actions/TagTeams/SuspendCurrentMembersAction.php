<?php

declare(strict_types=1);

namespace App\Actions\TagTeams;

use App\Actions\Managers\SuspendAction as SuspendManagerAction;
use App\Actions\Wrestlers\SuspendAction as SuspendWrestlerAction;
use App\Lifecycle\Roster\Individuals\IndividualSuspensionEligibility;
use App\Models\Roster\Managers\Manager;
use App\Models\Roster\TagTeams\TagTeam;
use App\Models\Roster\Wrestlers\Wrestler;
use Illuminate\Support\Carbon;

class SuspendCurrentMembersAction
{
    public function __construct(
        private readonly SuspendWrestlerAction $suspendWrestler,
        private readonly SuspendManagerAction $suspendManager,
        private readonly IndividualSuspensionEligibility $eligibility,
    ) {}

    public function handle(TagTeam $tagTeam, Carbon $suspensionDate): void
    {
        $wrestlers = $tagTeam->currentWrestlers()
            ->inLockOrder()
            ->get()
            ->filter(fn (Wrestler $wrestler): bool => $this->eligibility->canSuspend($wrestler));

        foreach ($wrestlers as $wrestler) {
            $this->suspendWrestler->handle($wrestler, $suspensionDate);
        }

        $managers = $tagTeam->currentManagers()
            ->inLockOrder()
            ->get()
            ->filter(fn (Manager $manager): bool => $this->eligibility->canSuspend($manager));

        foreach ($managers as $manager) {
            $this->suspendManager->handle($manager, $suspensionDate);
        }
    }
}

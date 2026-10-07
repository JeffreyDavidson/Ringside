<?php

declare(strict_types=1);

namespace App\Actions\TagTeams;

use App\Actions\Managers\ReinstateAction as ReinstateManagerAction;
use App\Actions\Wrestlers\ReinstateAction as ReinstateWrestlerAction;
use App\Lifecycle\Roster\Individuals\IndividualSuspensionEligibility;
use App\Models\Roster\Managers\Manager;
use App\Models\Roster\TagTeams\TagTeam;
use App\Models\Roster\Wrestlers\Wrestler;
use Illuminate\Support\Carbon;

class ReinstateCurrentMembersAction
{
    public function __construct(
        private readonly ReinstateWrestlerAction $reinstateWrestler,
        private readonly ReinstateManagerAction $reinstateManager,
        private readonly IndividualSuspensionEligibility $eligibility,
    ) {}

    public function handle(TagTeam $tagTeam, Carbon $reinstatementDate): void
    {
        $wrestlers = $tagTeam->currentWrestlers()
            ->inLockOrder()
            ->get()
            ->filter(fn (Wrestler $wrestler): bool => $this->eligibility->canReinstate($wrestler));

        foreach ($wrestlers as $wrestler) {
            $this->reinstateWrestler->handle($wrestler, $reinstatementDate);
        }

        $managers = $tagTeam->currentManagers()
            ->inLockOrder()
            ->get()
            ->filter(fn (Manager $manager): bool => $this->eligibility->canReinstate($manager));

        foreach ($managers as $manager) {
            $this->reinstateManager->handle($manager, $reinstatementDate);
        }
    }
}

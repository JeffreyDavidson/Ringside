<?php

declare(strict_types=1);

namespace App\Actions\TagTeams;

use App\Actions\Managers\UnretireAction as UnretireManagerAction;
use App\Actions\Wrestlers\UnretireAction as UnretireWrestlerAction;
use App\Exceptions\Roster\Individuals\CannotBeUnretiredException;
use App\Lifecycle\Roster\Individuals\IndividualRetirementEligibility;
use App\Models\Roster\Managers\Manager;
use App\Models\Roster\TagTeams\TagTeam;
use App\Models\Roster\Wrestlers\Wrestler;
use Illuminate\Support\Carbon;

class UnretireCurrentMembersAction
{
    public function __construct(
        private readonly UnretireWrestlerAction $unretireWrestler,
        private readonly UnretireManagerAction $unretireManager,
        private readonly IndividualRetirementEligibility $eligibility,
    ) {}

    public function handle(TagTeam $tagTeam, Carbon $unretirementDate): void
    {
        $wrestlers = $tagTeam->currentWrestlers()
            ->inLockOrder()
            ->get()
            ->filter(fn (Wrestler $wrestler): bool => $this->eligibility->canUnretire($wrestler));

        foreach ($wrestlers as $wrestler) {
            try {
                $this->unretireWrestler->handle($wrestler, $unretirementDate, false);
            } catch (CannotBeUnretiredException) {
                continue;
            }
        }

        $managers = $tagTeam->currentManagers()
            ->inLockOrder()
            ->get()
            ->filter(fn (Manager $manager): bool => $this->eligibility->canUnretire($manager));

        foreach ($managers as $manager) {
            try {
                $this->unretireManager->handle($manager, $unretirementDate, false);
            } catch (CannotBeUnretiredException) {
                continue;
            }
        }
    }
}

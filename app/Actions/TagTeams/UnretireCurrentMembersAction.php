<?php

declare(strict_types=1);

namespace App\Actions\TagTeams;

use App\Actions\Managers\UnretireAction as UnretireManagerAction;
use App\Actions\Wrestlers\UnretireAction as UnretireWrestlerAction;
use App\Exceptions\Roster\Individuals\CannotBeUnretiredException;
use App\Models\Roster\Managers\Manager;
use App\Models\Roster\TagTeams\TagTeam;
use App\Models\Roster\Wrestlers\Wrestler;
use Illuminate\Support\Carbon;

class UnretireCurrentMembersAction
{
    public function __construct(
        private readonly UnretireWrestlerAction $unretireWrestler,
        private readonly UnretireManagerAction $unretireManager,
    ) {}

    public function handle(TagTeam $tagTeam, Carbon $unretirementDate): void
    {
        $wrestlers = $tagTeam->currentWrestlers
            ->filter(fn (Wrestler $wrestler) => $wrestler->isRetired());

        foreach ($wrestlers as $wrestler) {
            try {
                $this->unretireWrestler->handle($wrestler, $unretirementDate, false);
            } catch (CannotBeUnretiredException) {
                continue;
            }
        }

        $managers = $tagTeam->currentManagers
            ->filter(fn (Manager $manager) => $manager->isRetired());

        foreach ($managers as $manager) {
            try {
                $this->unretireManager->handle($manager, $unretirementDate, false);
            } catch (CannotBeUnretiredException) {
                continue;
            }
        }
    }
}

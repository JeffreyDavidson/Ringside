<?php

declare(strict_types=1);

namespace App\Actions\TagTeams;

use App\Actions\Managers\UnretireAction as UnretireManagerAction;
use App\Actions\Wrestlers\UnretireAction as UnretireWrestlerAction;
use App\Models\Managers\Manager;
use App\Models\TagTeams\TagTeam;
use App\Models\Wrestlers\Wrestler;
use Exception;
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
            } catch (Exception) {
                continue;
            }
        }

        $managers = $tagTeam->currentManagers
            ->filter(fn (Manager $manager) => $manager->isRetired());

        foreach ($managers as $manager) {
            try {
                $this->unretireManager->handle($manager, $unretirementDate, false);
            } catch (Exception) {
                continue;
            }
        }
    }
}

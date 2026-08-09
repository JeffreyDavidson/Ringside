<?php

declare(strict_types=1);

namespace App\Actions\TagTeams;

use App\Actions\Managers\RetireAction as RetireManagerAction;
use App\Actions\Wrestlers\RetireAction as RetireWrestlerAction;
use App\Models\Managers\Manager;
use App\Models\TagTeams\TagTeam;
use App\Models\Wrestlers\Wrestler;
use Illuminate\Support\Carbon;

class RetireCurrentMembersAction
{
    public function __construct(
        private readonly RetireWrestlerAction $retireWrestler,
        private readonly RetireManagerAction $retireManager,
    ) {}

    public function handle(TagTeam $tagTeam, Carbon $retirementDate): void
    {
        $wrestlers = $tagTeam->currentWrestlers()
            ->get()
            ->filter(fn (Wrestler $wrestler) => $wrestler->canBeRetired());

        foreach ($wrestlers as $wrestler) {
            $this->retireWrestler->handle($wrestler, $retirementDate);
        }

        $managers = $tagTeam->currentManagers()
            ->get()
            ->filter(fn (Manager $manager) => $manager->canBeRetired());

        foreach ($managers as $manager) {
            $this->retireManager->handle($manager, $retirementDate);
        }
    }
}

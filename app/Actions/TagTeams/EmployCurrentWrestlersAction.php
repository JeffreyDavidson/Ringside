<?php

declare(strict_types=1);

namespace App\Actions\TagTeams;

use App\Actions\Wrestlers\EmployAction as EmployWrestlerAction;
use App\Models\Roster\TagTeams\TagTeam;
use App\Models\Roster\Wrestlers\Wrestler;
use Illuminate\Support\Carbon;

class EmployCurrentWrestlersAction
{
    public function __construct(private readonly EmployWrestlerAction $employWrestler) {}

    public function handle(TagTeam $tagTeam, Carbon $employmentDate): void
    {
        $wrestlers = $tagTeam->currentWrestlers()
            ->get()
            ->filter(fn (Wrestler $wrestler): bool => ! $wrestler->isEmployed() && ! $wrestler->hasFutureEmployment());

        foreach ($wrestlers as $wrestler) {
            $this->employWrestler->handle($wrestler, $employmentDate);
        }
    }
}

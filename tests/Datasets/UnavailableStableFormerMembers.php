<?php

declare(strict_types=1);

use App\Models\Roster\Stables\Stable;
use App\Models\Roster\TagTeams\TagTeam;
use App\Models\Roster\Wrestlers\Wrestler;

/*
 * Each case takes a stable with former members and makes one of them
 * unavailable, returning that member so tests can assert on its name.
 */
dataset('unavailable stable former members', [
    'suspended wrestler' => [function (Stable $stable): Wrestler {
        $wrestler = $stable->previousWrestlers()->firstOrFail();
        $wrestler->suspensions()->create(['started_at' => now()->subHour()]);

        return $wrestler;
    }],
    'injured wrestler' => [function (Stable $stable): Wrestler {
        $wrestler = $stable->previousWrestlers()->firstOrFail();
        $wrestler->injuries()->create(['started_at' => now()->subHour()]);

        return $wrestler;
    }],
    'retired wrestler' => [function (Stable $stable): Wrestler {
        $wrestler = $stable->previousWrestlers()->firstOrFail();
        $wrestler->retirements()->create(['started_at' => now()->subHour()]);

        return $wrestler;
    }],
    'wrestler in another stable' => [function (Stable $stable): Wrestler {
        $wrestler = $stable->previousWrestlers()->firstOrFail();
        Stable::factory()->create()->wrestlers()->attach($wrestler, ['joined_at' => now()->subHour()]);

        return $wrestler;
    }],
    'suspended tag team' => [function (Stable $stable): TagTeam {
        $tagTeam = $stable->previousTagTeams()->get()->firstOrFail();
        $tagTeam->suspensions()->create(['started_at' => now()->subHour()]);

        return $tagTeam;
    }],
    'retired tag team' => [function (Stable $stable): TagTeam {
        $tagTeam = $stable->previousTagTeams()->get()->firstOrFail();
        $tagTeam->retirements()->create(['started_at' => now()->subHour()]);

        return $tagTeam;
    }],
    'tag team in another stable' => [function (Stable $stable): TagTeam {
        $tagTeam = $stable->previousTagTeams()->get()->firstOrFail();
        Stable::factory()->create()->tagTeams()->attach($tagTeam, ['joined_at' => now()->subHour()]);

        return $tagTeam;
    }],
]);

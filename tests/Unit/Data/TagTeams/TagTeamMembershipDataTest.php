<?php

declare(strict_types=1);

use App\Data\TagTeams\TagTeamMembershipData;
use App\Models\Roster\Managers\Manager;
use App\Models\Roster\Wrestlers\Wrestler;
use Illuminate\Database\Eloquent\Collection;

test('creates membership data with typed managers', function () {
    $manager = Manager::factory()->make();

    $members = TagTeamMembershipData::fromWrestlers(
        Wrestler::factory()->make(),
        Wrestler::factory()->make(),
        new Collection([$manager]),
    );

    expect($members->getManagers())->toContain($manager);
});

<?php

declare(strict_types=1);

use App\Data\Stables\StableMembershipData;
use App\Models\TagTeams\TagTeam;
use App\Models\Wrestlers\Wrestler;
use Illuminate\Database\Eloquent\Collection;

it('counts individual wrestlers as one and tag teams as two', function () {
    $members = new StableMembershipData(
        wrestlers: new Collection([new Wrestler()]),
        tagTeams: new Collection([new TagTeam(), new TagTeam()]),
    );

    expect($members->getTotalMemberCount())->toBe(5);
});

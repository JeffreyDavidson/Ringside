<?php

declare(strict_types=1);

use App\Data\Stables\StableMembershipData;
use App\Models\TagTeams\TagTeam;
use App\Models\Wrestlers\Wrestler;
use Illuminate\Database\Eloquent\Collection;

it('counts individual wrestlers as one and tag teams as two', function () {
    $members = new StableMembershipData(
        wrestlers: Wrestler::factory()->count(1)->make(),
        tagTeams: TagTeam::factory()->count(2)->make(),
    );

    expect($members->getTotalMemberCount())->toBe(5);
});

it('reports retired employed members as unavailable', function () {
    $wrestler = Wrestler::factory()->employed()->create();
    $wrestler->retirements()->create(['started_at' => now()]);
    $tagTeam = TagTeam::factory()->employed()->create();
    $tagTeam->retirements()->create(['started_at' => now()]);

    $members = new StableMembershipData(
        wrestlers: new Collection([$wrestler]),
        tagTeams: new Collection([$tagTeam]),
    );

    expect($members->getUnavailableMemberNames())
        ->toEqualCanonicalizing([$wrestler->name, $tagTeam->name]);
});

<?php

declare(strict_types=1);

use App\Data\Stables\StableMembershipData;
use App\Models\TagTeams\TagTeam;
use App\Models\Wrestlers\Wrestler;

it('counts individual wrestlers as one and tag teams as two', function () {
    $members = new StableMembershipData(
        wrestlers: Wrestler::factory()->count(1)->make(),
        tagTeams: TagTeam::factory()->count(2)->make(),
    );

    expect($members->getTotalMemberCount())->toBe(5);
});

it('reports whether the payload contains members', function () {
    $emptyMembers = new StableMembershipData();
    $members = new StableMembershipData(wrestlers: Wrestler::factory()->count(1)->make());

    expect($emptyMembers->isEmpty())->toBeTrue()
        ->and($emptyMembers->isNotEmpty())->toBeFalse()
        ->and($members->isEmpty())->toBeFalse()
        ->and($members->isNotEmpty())->toBeTrue();
});

it('reports whether the weighted headcount meets the minimum', function () {
    $belowMinimum = new StableMembershipData(
        wrestlers: Wrestler::factory()->count(2)->make(),
    );
    $atMinimum = new StableMembershipData(
        wrestlers: Wrestler::factory()->count(1)->make(),
        tagTeams: TagTeam::factory()->count(1)->make(),
    );

    expect(StableMembershipData::MINIMUM_MEMBER_COUNT)->toBe(3)
        ->and($belowMinimum->hasMinimumMembers())->toBeFalse()
        ->and($atMinimum->hasMinimumMembers())->toBeTrue();
});

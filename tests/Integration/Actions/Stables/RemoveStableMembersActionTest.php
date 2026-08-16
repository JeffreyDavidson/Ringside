<?php

declare(strict_types=1);

use App\Actions\Stables\RemoveStableMembersAction;
use App\Data\Stables\StableMembershipData;
use App\Exceptions\Lifecycle\InvalidDateRangeException;
use App\Models\Roster\Stables\Stable;
use App\Models\Roster\Stables\StableWrestler;
use App\Models\Roster\Wrestlers\Wrestler;
use Illuminate\Database\Eloquent\Collection;

test('it rejects a future membership removal date', function () {
    $stable = Stable::factory()->create();
    $wrestler = Wrestler::factory()->create();
    $stable->wrestlers()->attach($wrestler, ['joined_at' => today()]);
    $members = new StableMembershipData(wrestlers: new Collection([$wrestler]));

    expect(fn () => resolve(RemoveStableMembersAction::class)->handle(
        $stable,
        $members,
        now()->addDay(),
    ))->toThrow(InvalidDateRangeException::class);

    expect(StableWrestler::query()
        ->whereBelongsTo($stable)
        ->whereBelongsTo($wrestler)
        ->whereNull('left_at')
        ->exists())->toBeTrue();
});

test('it ends current stable memberships on the removal date', function () {
    $stable = Stable::factory()->create();
    $wrestler = Wrestler::factory()->create();
    $stable->wrestlers()->attach($wrestler, ['joined_at' => today()->subDay()]);
    $members = new StableMembershipData(wrestlers: new Collection([$wrestler]));
    $removalDate = now()->startOfSecond();

    resolve(RemoveStableMembersAction::class)->handle(
        $stable,
        $members,
        $removalDate,
    );

    $membership = StableWrestler::query()
        ->whereBelongsTo($stable)
        ->whereBelongsTo($wrestler)
        ->firstOrFail();

    expect($membership->left_at)->not->toBeNull()
        ->and($membership->left_at?->equalTo($removalDate))->toBeTrue();
});

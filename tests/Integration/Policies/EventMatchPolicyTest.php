<?php

declare(strict_types=1);

use App\Models\Matches\EventMatch;
use App\Policies\EventMatchPolicy;
use Illuminate\Support\Facades\Gate;

it('resolves the event match policy for matches', function () {
    $eventMatch = EventMatch::factory()->create();

    $policy = Gate::getPolicyFor($eventMatch);

    expect($policy)->toBeInstanceOf(EventMatchPolicy::class);
});

it('denies every instance ability on a match to users without a promotion role', function (string $ability) {
    $eventMatch = EventMatch::factory()->create();
    $basicUser = basicUser();

    $decision = Gate::forUser($basicUser)->inspect($ability, $eventMatch);

    expect($decision->allowed())->toBeFalse();
})->with(['view', 'update', 'delete', 'restore']);

it('denies every class ability on matches to users without a promotion role', function (string $ability) {
    $basicUser = basicUser();

    $decision = Gate::forUser($basicUser)->inspect($ability, EventMatch::class);

    expect($decision->allowed())->toBeFalse();
})->with(['viewAny', 'create']);

it('lets administrators manage matches', function (string $ability) {
    $eventMatch = EventMatch::factory()->create();
    $administrator = administrator();

    $allowed = Gate::forUser($administrator)->allows($ability, $eventMatch);

    expect($allowed)->toBeTrue();
})->with(['view', 'update', 'delete', 'restore']);

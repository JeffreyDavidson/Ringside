<?php

declare(strict_types=1);

use App\Lifecycle\TagTeamMembershipRequirements;
use App\Models\Roster\Wrestlers\Wrestler;
use Illuminate\Database\Eloquent\Collection;

test('a tag team requires at least two current wrestlers', function (int $wrestlerCount, bool $satisfiesRequirement): void {
    $wrestlers = new Collection(
        Wrestler::factory()->count($wrestlerCount)->make()->all(),
    );

    expect(TagTeamMembershipRequirements::hasMinimumCurrentWrestlers($wrestlers))
        ->toBe($satisfiesRequirement);
})->with([
    'no wrestlers' => [0, false],
    'one wrestler' => [1, false],
    'two wrestlers' => [2, true],
    'more than two wrestlers' => [3, true],
]);

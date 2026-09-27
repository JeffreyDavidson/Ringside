<?php

declare(strict_types=1);

use App\Lifecycle\Roster\Stables\StableMembershipRequirements;

it('determines whether a stable headcount meets the minimum', function (int $headcount, bool $expected) {
    expect(StableMembershipRequirements::hasMinimumHeadcount($headcount))->toBe($expected);
})->with([
    'below the minimum' => [2, false],
    'at the minimum' => [3, true],
    'above the minimum' => [4, true],
]);

<?php

declare(strict_types=1);

use App\Enums\Shared\UnitedStatesState;

it('contains every supported United States state and district', function () {
    expect(UnitedStatesState::cases())->toHaveCount(51)
        ->and(UnitedStatesState::California->value)->toBe('California')
        ->and(UnitedStatesState::DistrictOfColumbia->value)->toBe('District of Columbia')
        ->and(UnitedStatesState::NewYork->value)->toBe('New York');
});

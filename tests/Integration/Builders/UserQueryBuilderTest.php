<?php

declare(strict_types=1);

use App\Builders\Users\UserBuilder;
use App\Models\Users\User;

test('user queries use the typed builder', function () {
    expect(User::query())->toBeInstanceOf(UserBuilder::class)
        ->and(User::query()->whereNameMatches('Jeffrey'))->toBeInstanceOf(UserBuilder::class);
});

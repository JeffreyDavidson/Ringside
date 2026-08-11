<?php

declare(strict_types=1);

use App\Exceptions\Roster\CannotBeReinstatedException;
use App\Models\Wrestlers\Wrestler;

test('reinstatement predicate matches its guard for an injured suspended wrestler', function () {
    $wrestler = Wrestler::factory()->suspended()->create();
    $wrestler->injuries()->create([
        'started_at' => now()->subDay(),
        'ended_at' => null,
    ]);

    $canBeReinstated = $wrestler->canBeReinstated();

    expect($canBeReinstated)->toBeFalse();
    expect(fn () => $wrestler->ensureCanBeReinstated())
        ->toThrow(CannotBeReinstatedException::class);
});

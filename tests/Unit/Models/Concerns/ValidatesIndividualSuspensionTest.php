<?php

declare(strict_types=1);

use App\Exceptions\Roster\Individuals\CannotBeReinstatedException;
use App\Models\Referees\Referee;
use App\Models\Wrestlers\Wrestler;

test('reinstatement predicate matches its guard for suspended bookable individuals', function () {
    $individuals = [
        Wrestler::factory()->suspended()->create(),
        Referee::factory()->suspended()->create(),
    ];

    foreach ($individuals as $individual) {
        expect($individual->isBookable())->toBeFalse()
            ->and($individual->canBeReinstated())->toBeTrue()
            ->and(fn () => $individual->ensureCanBeReinstated())->not->toThrow(CannotBeReinstatedException::class);
    }
});

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

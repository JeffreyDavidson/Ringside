<?php

declare(strict_types=1);

use App\Models\Roster\Wrestlers\Wrestler;
use App\ValueObjects\Weight;

test('it casts stored pounds to weight', function () {
    $wrestler = Wrestler::factory()->create(['weight' => 225]);

    expect($wrestler->weight)->toEqual(new Weight(225));
});

test('it stores weight values as pounds', function () {
    $wrestler = Wrestler::factory()->create(['weight' => new Weight(225)]);

    expect($wrestler->getRawOriginal('weight'))->toBe(225)
        ->and($wrestler->weight)->toEqual(new Weight(225));
});

test('it rejects invalid weight values', function (int $weight) {
    expect(fn () => Wrestler::factory()->create(['weight' => $weight]))
        ->toThrow(InvalidArgumentException::class);
})->with([0, -1]);

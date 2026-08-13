<?php

declare(strict_types=1);

use App\Models\Wrestlers\Wrestler;
use App\ValueObjects\Height;

test('it casts stored inches to height', function () {
    $wrestler = Wrestler::factory()->create(['height' => 74]);

    expect($wrestler->height)->toEqual(new Height(6, 2));
});

test('it stores height values as total inches', function () {
    $wrestler = Wrestler::factory()->create(['height' => new Height(6, 2)]);

    expect($wrestler->getRawOriginal('height'))->toBe(74)
        ->and($wrestler->height)->toEqual(new Height(6, 2));
});

test('it rejects invalid stored height values', function () {
    expect(fn () => Wrestler::factory()->create(['height' => 0]))
        ->toThrow(InvalidArgumentException::class);
});

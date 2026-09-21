<?php

declare(strict_types=1);

use App\ValueObjects\Height;

test('it creates height from feet and inches', function () {
    $height = new Height(6, 2);

    expect($height->toInches())->toBe(74)
        ->and((string) $height)->toBe('6\'2"');
});

test('it creates height from total inches', function () {
    $height = Height::fromInches(74);

    expect($height->feet)->toBe(6)
        ->and($height->inches)->toBe(2);
});

test('it rejects invalid height components', function (int $feet, int $inches) {
    expect(fn () => new Height($feet, $inches))->toThrow(InvalidArgumentException::class);
})->with([
    'negative feet' => [-1, 0],
    'negative inches' => [6, -1],
    'too many inches' => [6, 12],
    'zero height' => [0, 0],
]);

test('it rejects invalid total inches', function (int $inches) {
    expect(fn () => Height::fromInches($inches))->toThrow(InvalidArgumentException::class);
})->with([0, -1]);

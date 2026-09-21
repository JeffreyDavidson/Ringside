<?php

declare(strict_types=1);

use App\ValueObjects\Weight;

test('it represents weight in pounds', function () {
    $weight = new Weight(225);

    expect($weight->toPounds())->toBe(225)
        ->and((string) $weight)->toBe('225 lbs');
});

test('it rejects non-positive weight', function (int $pounds) {
    expect(fn () => new Weight($pounds))->toThrow(InvalidArgumentException::class);
})->with([0, -1]);

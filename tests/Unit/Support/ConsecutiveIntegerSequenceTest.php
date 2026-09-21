<?php

declare(strict_types=1);

use App\Support\ConsecutiveIntegerSequence;

test('recognizes consecutive one-based integer sequences', function (array $values, bool $isValid) {
    expect(resolve(ConsecutiveIntegerSequence::class)->isValid($values))->toBe($isValid);
})->with([
    'empty sequence' => [[], true],
    'single value' => [[1], true],
    'ordered sequence' => [[1, 2, 3], true],
    'unordered sequence' => [[3, 1, 2], true],
    'starts after one' => [[2, 3], false],
    'contains a gap' => [[1, 3], false],
    'contains a duplicate' => [[1, 2, 2], false],
    'contains a missing value' => [[1, null, 3], false],
]);

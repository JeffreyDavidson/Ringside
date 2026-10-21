<?php

declare(strict_types=1);

use App\ValueObjects\BracketShape;

describe('building', function (): void {
    it('refuses a capacity that is not a power of two of at least two', function (int $capacity): void {
        // Arrange
        $build = fn () => new BracketShape($capacity);

        // Act and Assert
        expect($build)->toThrow(InvalidArgumentException::class, 'A bracket capacity must be a power of two of at least 2.');
    })->with([-4, 0, 1, 3, 6, 12]);
});

describe('rounds and the final', function (): void {
    it('counts the rounds of the bracket', function (int $capacity, int $rounds): void {
        // Arrange
        $shape = new BracketShape($capacity);

        // Act
        $counted = $shape->rounds();

        // Assert
        expect($counted)->toBe($rounds);
    })->with([[2, 1], [4, 2], [8, 3], [16, 4]]);

    it('knows which round is the final', function (int $capacity, int $round, bool $isFinal): void {
        // Arrange
        $shape = new BracketShape($capacity);

        // Act
        $answer = $shape->isFinal($round);

        // Assert
        expect($answer)->toBe($isFinal);
    })->with([[2, 1, true], [4, 1, false], [4, 2, true], [8, 2, false], [8, 3, true], [16, 3, false], [16, 4, true]]);
});

describe('seeds and slots', function (): void {
    it('orders the seeds so the top seeds meet last', function (int $capacity, array $seeds): void {
        // Arrange
        $shape = new BracketShape($capacity);

        // Act
        $ordered = $shape->seeds();

        // Assert
        expect($ordered)->toBe($seeds);
    })->with([
        [2, [1, 2]],
        [4, [1, 4, 2, 3]],
        [8, [1, 8, 4, 5, 2, 7, 3, 6]],
        [16, [1, 16, 8, 9, 4, 13, 5, 12, 2, 15, 7, 10, 3, 14, 6, 11]],
    ]);

    it('lists every node by round then position with first round seeds only', function (int $capacity, array $slots): void {
        // Arrange
        $shape = new BracketShape($capacity);

        // Act
        $listed = $shape->slots();

        // Assert
        expect($listed)->toBe($slots);
    })->with([
        [2, [[1, 1, 1, 2]]],
        [4, [[1, 1, 1, 4], [1, 2, 2, 3], [2, 1, null, null]]],
        [8, [[1, 1, 1, 8], [1, 2, 4, 5], [1, 3, 2, 7], [1, 4, 3, 6], [2, 1, null, null], [2, 2, null, null], [3, 1, null, null]]],
        [16, [
            [1, 1, 1, 16], [1, 2, 8, 9], [1, 3, 4, 13], [1, 4, 5, 12], [1, 5, 2, 15], [1, 6, 7, 10], [1, 7, 3, 14], [1, 8, 6, 11],
            [2, 1, null, null], [2, 2, null, null], [2, 3, null, null], [2, 4, null, null],
            [3, 1, null, null], [3, 2, null, null],
            [4, 1, null, null],
        ]],
    ]);
});

describe('feeders, ancestors and descendants', function (): void {
    it('names the two nodes that feed a node, none in the first round', function (int $capacity, int $round, int $position, array $feeders): void {
        // Arrange
        $shape = new BracketShape($capacity);

        // Act
        $found = $shape->feeders($round, $position);

        // Assert
        expect($found)->toBe($feeders);
    })->with([
        [2, 1, 1, []],
        [4, 1, 2, []],
        [4, 2, 1, [[1, 1], [1, 2]]],
        [8, 3, 1, [[2, 1], [2, 2]]],
        [8, 2, 2, [[1, 3], [1, 4]]],
        [16, 4, 1, [[3, 1], [3, 2]]],
        [16, 3, 2, [[2, 3], [2, 4]]],
    ]);

    it('lists ancestors from the round below down to the first, in position order', function (int $capacity, int $round, int $position, array $ancestors): void {
        // Arrange
        $shape = new BracketShape($capacity);

        // Act
        $found = $shape->ancestors($round, $position);

        // Assert
        expect($found)->toBe($ancestors);
    })->with([
        [2, 1, 1, []],
        [4, 1, 2, []],
        [4, 2, 1, [[1, 1], [1, 2]]],
        [8, 3, 1, [[2, 1], [2, 2], [1, 1], [1, 2], [1, 3], [1, 4]]],
        [8, 2, 2, [[1, 3], [1, 4]]],
        [16, 3, 2, [[2, 3], [2, 4], [1, 5], [1, 6], [1, 7], [1, 8]]],
        [16, 4, 1, [[3, 1], [3, 2], [2, 1], [2, 2], [2, 3], [2, 4], [1, 1], [1, 2], [1, 3], [1, 4], [1, 5], [1, 6], [1, 7], [1, 8]]],
    ]);

    it('lists descendants from the next round up to the final', function (int $capacity, int $round, int $position, array $descendants): void {
        // Arrange
        $shape = new BracketShape($capacity);

        // Act
        $found = $shape->descendants($round, $position);

        // Assert
        expect($found)->toBe($descendants);
    })->with([
        [2, 1, 1, []],
        [4, 1, 2, [[2, 1]]],
        [4, 2, 1, []],
        [8, 1, 3, [[2, 2], [3, 1]]],
        [16, 1, 7, [[2, 4], [3, 2], [4, 1]]],
        [16, 3, 2, [[4, 1]]],
        [16, 4, 1, []],
    ]);
});

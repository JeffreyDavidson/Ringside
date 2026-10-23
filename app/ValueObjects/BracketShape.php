<?php

declare(strict_types=1);

namespace App\ValueObjects;

use InvalidArgumentException;

/**
 * The shape of a single-elimination bracket built from its capacity: how many
 * rounds it has, how the first round is seeded and which nodes feed which.
 *
 * A node is addressed by its round and position, both counted from 1. The
 * value object only answers questions; it never reads or writes nodes.
 */
final readonly class BracketShape
{
    public function __construct(public int $capacity)
    {
        if ($capacity < 2 || ($capacity & ($capacity - 1)) !== 0) {
            throw new InvalidArgumentException('A bracket capacity must be a power of two of at least 2.');
        }
    }

    public function rounds(): int
    {
        return (int) log($this->capacity, 2);
    }

    public function isFinal(int $round): bool
    {
        return $round === $this->rounds();
    }

    /**
     * First round seeds in bracket order, two per node, so the top seeds meet last.
     *
     * @return list<int>
     */
    public function seeds(): array
    {
        $seeds = [1, 2];

        for ($size = 4; $size <= $this->capacity; $size *= 2) {
            $seeds = array_merge(...array_map(fn (int $seed): array => [$seed, $size + 1 - $seed], $seeds));
        }

        return $seeds;
    }

    /**
     * Every node by round then position as [round, position, first seed, second seed].
     * Only first round nodes carry seeds.
     *
     * @return list<array{int, int, ?int, ?int}>
     */
    public function slots(): array
    {
        $seeds = $this->seeds();
        $slots = [];

        for ($round = 1; $round <= $this->rounds(); $round++) {
            for ($position = 1; $position <= $this->capacity / 2 ** $round; $position++) {
                $slots[] = [
                    $round,
                    $position,
                    $round === 1 ? $seeds[($position - 1) * 2] : null,
                    $round === 1 ? $seeds[($position - 1) * 2 + 1] : null,
                ];
            }
        }

        return $slots;
    }

    /**
     * The two nodes of the round below that feed this node; none in the first round.
     *
     * @return list<array{int, int}>
     */
    public function feeders(int $round, int $position): array
    {
        if ($round === 1) {
            return [];
        }

        return [[$round - 1, $position * 2 - 1], [$round - 1, $position * 2]];
    }

    /**
     * Every node that feeds this one, nearest round first and by position within a round.
     *
     * @return list<array{int, int}>
     */
    public function ancestors(int $round, int $position): array
    {
        $ancestors = [];
        $positions = [$position];

        for ($below = $round - 1; $below >= 1; $below--) {
            $positions = array_merge(...array_map(fn (int $above): array => [$above * 2 - 1, $above * 2], $positions));

            foreach ($positions as $ancestor) {
                $ancestors[] = [$below, $ancestor];
            }
        }

        return $ancestors;
    }

    /**
     * The node each later round sends the winner to, up to the final.
     *
     * @return list<array{int, int}>
     */
    public function descendants(int $round, int $position): array
    {
        $descendants = [];

        for ($next = $round + 1; $next <= $this->rounds(); $next++) {
            $position = (int) ceil($position / 2);
            $descendants[] = [$next, $position];
        }

        return $descendants;
    }
}

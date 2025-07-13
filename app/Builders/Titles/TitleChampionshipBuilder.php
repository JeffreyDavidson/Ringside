<?php

declare(strict_types=1);

namespace App\Builders\Titles;

use App\Models\Titles\TitleChampionship;
use Illuminate\Database\Eloquent\Builder;

/**
 * Custom query builder for the TitleChampionship model.
 *
 * Provides specialized query methods for working with title championships,
 * including filtering by current/previous status and ordering by dates.
 *
 * @extends Builder<TitleChampionship>
 *
 * @example
 * ```php
 * // Get current championships
 * $currentChampionships = TitleChampionship::query()->current()->get();
 *
 * // Get championship history with reign lengths
 * $history = TitleChampionship::query()
 *     ->previous()
 *     ->withReignLength()
 *     ->latestLost()
 *     ->get();
 * ```
 */
class TitleChampionshipBuilder extends Builder
{
    /**
     * Filter championships that are currently held (i.e., not yet lost).
     *
     * Returns championships where the 'lost_at' field is null, indicating
     * that the title is still actively held by the champion.
     *
     * @return static The builder instance for method chaining
     *
     * @example
     * ```php
     * $currentChamps = TitleChampionship::query()->current()->get();
     * ```
     */
    public function current(): static
    {
        /** @var static */
        return $this->whereNull('lost_at');
    }

    /**
     * Filter championships that have been lost.
     *
     * Returns championships where the 'lost_at' field is not null, indicating
     * that the title reign has ended.
     *
     * @return static The builder instance for method chaining
     *
     * @example
     * ```php
     * $formerChamps = TitleChampionship::query()->previous()->get();
     * ```
     */
    public function previous(): static
    {
        /** @var static */
        return $this->whereNotNull('lost_at');
    }

    /**
     * Order championships by the most recent win date (descending).
     *
     * Orders results by 'won_at' in descending order, showing the most
     * recently won championships first.
     *
     * @return static The builder instance for method chaining
     *
     * @example
     * ```php
     * $recentWins = TitleChampionship::query()->latestWon()->get();
     * ```
     */
    public function latestWon(): static
    {
        /** @var static */
        return $this->latest('won_at');
    }

    /**
     * Order championships by the most recent loss date (descending).
     *
     * Orders results by 'lost_at' in descending order, showing the most
     * recently lost championships first.
     *
     * @return static The builder instance for method chaining
     *
     * @example
     * ```php
     * $recentLosses = TitleChampionship::query()->previous()->latestLost()->get();
     * ```
     */
    public function latestLost(): static
    {
        /** @var static */
        return $this->latest('lost_at');
    }

    /**
     * Select all columns and calculate reign length in days as `reign_length`.
     *
     * Adds a calculated column that shows the length of the championship reign
     * in days. For current championships, calculates from won_at to now.
     * For previous championships, calculates from won_at to lost_at.
     *
     * Uses database-agnostic date calculations to support both MySQL and SQLite.
     *
     * @return static The builder instance for method chaining
     *
     * @example
     * ```php
     * $championshipsWithLength = TitleChampionship::query()
     *     ->withReignLength()
     *     ->get();
     *
     * foreach ($championshipsWithLength as $championship) {
     *     echo "Reign length: " . $championship->reign_length . " days";
     * }
     * ```
     */
    public function withReignLength(): static
    {
        // Use database-agnostic date calculation
        $driverName = \Illuminate\Support\Facades\DB::connection()->getDriverName();

        $reignLengthSql = match ($driverName) {
            'mysql' => 'DATEDIFF(COALESCE(lost_at, NOW()), won_at) as reign_length',
            'sqlite' => 'CAST((julianday(COALESCE(lost_at, date("now"))) - julianday(date(won_at))) AS INTEGER) as reign_length',
            default => 'DATEDIFF(COALESCE(lost_at, NOW()), won_at) as reign_length' // Default to MySQL syntax
        };

        /** @var static */
        return $this->selectRaw("*, {$reignLengthSql}");
    }
}

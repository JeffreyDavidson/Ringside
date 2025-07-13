<?php

declare(strict_types=1);

namespace App\Models\Matches;

use Database\Factories\Matches\MatchDecisionFactory;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $name
 * @property string $slug
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 *
 * @method static \Database\Factories\Matches\MatchDecisionFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MatchDecision newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MatchDecision newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MatchDecision query()
 *
 * @mixin \Eloquent
 */
#[UseFactory(MatchDecisionFactory::class)]
class MatchDecision extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'slug',
    ];

    /**
     * Decisions that result in no winners or losers.
     *
     * @var array<string>
     */
    protected const NO_OUTCOME_DECISIONS = [
        'draw',        // Time Limit Draw
        'nodecision',  // No Decision
        'revdecision', // Reverse Decision
    ];

    /**
     * Check if this decision type produces winners.
     *
     * @return bool
     */
    public function hasWinners(): bool
    {
        return !in_array($this->slug, self::NO_OUTCOME_DECISIONS, true);
    }

    /**
     * Check if this decision type produces losers.
     *
     * @return bool
     */
    public function hasLosers(): bool
    {
        return !in_array($this->slug, self::NO_OUTCOME_DECISIONS, true);
    }

    /**
     * Check if this decision results in no winners or losers.
     *
     * @return bool
     */
    public function hasNoOutcome(): bool
    {
        return in_array($this->slug, self::NO_OUTCOME_DECISIONS, true);
    }
}

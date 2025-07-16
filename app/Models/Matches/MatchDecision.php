<?php

declare(strict_types=1);

namespace App\Models\Matches;

use Database\Factories\Matches\MatchDecisionFactory;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

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
     * Boot the model.
     */
    protected static function boot(): void
    {
        parent::boot();

        static::creating(function ($matchDecision) {
            if (empty($matchDecision->slug) && !empty($matchDecision->name)) {
                $matchDecision->slug = Str::slug($matchDecision->name);
            }
        });

        static::updating(function ($matchDecision) {
            if ($matchDecision->isDirty('name') && !$matchDecision->isDirty('slug')) {
                $matchDecision->slug = Str::slug($matchDecision->name);
            }
        });
    }

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
     * @var array<int, string>
     */
    protected const NO_OUTCOME_DECISIONS = [
        'time-limit-draw', // Time Limit Draw
        'no-decision',     // No Decision
        'reverse-decision', // Reverse Decision
    ];

    /**
     * Check if this decision type produces winners.
     */
    public function hasWinners(): bool
    {
        return ! in_array($this->slug, self::NO_OUTCOME_DECISIONS, true);
    }

    /**
     * Check if this decision type produces losers.
     */
    public function hasLosers(): bool
    {
        return ! in_array($this->slug, self::NO_OUTCOME_DECISIONS, true);
    }

    /**
     * Check if this decision results in no winners or losers.
     */
    public function hasNoOutcome(): bool
    {
        return in_array($this->slug, self::NO_OUTCOME_DECISIONS, true);
    }
}

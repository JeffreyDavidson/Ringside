<?php

declare(strict_types=1);

namespace App\Models\Matches;

use Database\Factories\Matches\MatchTypeFactory;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * @property int $id
 * @property string $name
 * @property string $slug
 * @property int|null $number_of_sides
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 *
 * @method static \Database\Factories\Matches\MatchTypeFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MatchType newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MatchType newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MatchType query()
 *
 * @mixin \Eloquent
 */
#[UseFactory(MatchTypeFactory::class)]
class MatchType extends Model
{
    /** @use HasFactory<MatchTypeFactory> */
    use HasFactory;

    /**
     * Boot the model.
     */
    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (self $matchType) {
            if (empty($matchType->slug) && ! empty($matchType->name)) {
                $matchType->slug = Str::slug($matchType->name);
            }
        });

        static::updating(function (self $matchType) {
            if ($matchType->isDirty('name') && ! $matchType->isDirty('slug')) {
                $matchType->slug = Str::slug($matchType->name);
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
        'number_of_sides',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'number_of_sides' => 'integer',
        ];
    }

    /**
     * Get the allowed competitor types as an array.
     *
     * @return array<string>
     */
    public function getAllowedCompetitorTypes(): array
    {
        // Determine allowed types based on the match type slug
        return match ($this->slug) {
            'tag-team', 'mixed', 'tornado-tag-team', '6-man-tag-team', '8-man-tag-team', '10-man-tag-team' => ['wrestler', 'tag_team'],
            'triple-threat', 'fatal-4-way', 'battle-royal', 'royal-rumble' => ['wrestler', 'tag_team'],
            default => ['wrestler'], // Singles and other types default to wrestler-only
        };
    }

    /**
     * Check if this match type allows wrestler competitors.
     */
    public function allowsWrestlers(): bool
    {
        return in_array('wrestler', $this->getAllowedCompetitorTypes(), true);
    }

    /**
     * Check if this match type allows tag team competitors.
     */
    public function allowsTagTeams(): bool
    {
        return in_array('tag_team', $this->getAllowedCompetitorTypes(), true);
    }

    /**
     * Check if a specific competitor type is allowed in this match type.
     */
    public function allowsCompetitorType(string $competitorType): bool
    {
        return in_array($competitorType, $this->getAllowedCompetitorTypes(), true);
    }

    /**
     * Get the minimum number of competitors required for this match type.
     */
    public function getMinimumCompetitors(): int
    {
        return $this->number_of_sides ?? 2;
    }

    /**
     * Get the maximum number of competitors allowed for this match type.
     */
    public function getMaximumCompetitors(): int
    {
        // For now, assume same as minimum unless specified otherwise
        return $this->getMinimumCompetitors();
    }
}

<?php

declare(strict_types=1);

namespace App\Models\Matches;

use Database\Factories\Matches\MatchTypeFactory;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $name
 * @property string $slug
 * @property int|null $number_of_sides
 * @property array<string> $competitor_types JSON array of allowed competitor types (wrestler, tag_team)
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
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'slug',
        'number_of_sides',
        'competitor_types',
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
            'competitor_types' => 'array',
        ];
    }

    /**
     * Get the allowed competitor types as an array.
     *
     * @return array<string>
     */
    public function getAllowedCompetitorTypes(): array
    {
        if (empty($this->competitor_types)) {
            return ['wrestler']; // Default to wrestler-only
        }

        return $this->competitor_types;
    }

    /**
     * Check if this match type allows wrestler competitors.
     *
     * @return bool
     */
    public function allowsWrestlers(): bool
    {
        return in_array('wrestler', $this->getAllowedCompetitorTypes(), true);
    }

    /**
     * Check if this match type allows tag team competitors.
     *
     * @return bool
     */
    public function allowsTagTeams(): bool
    {
        return in_array('tag_team', $this->getAllowedCompetitorTypes(), true);
    }

    /**
     * Check if a specific competitor type is allowed in this match type.
     *
     * @param string $competitorType
     * @return bool
     */
    public function allowsCompetitorType(string $competitorType): bool
    {
        return in_array($competitorType, $this->getAllowedCompetitorTypes(), true);
    }

    /**
     * Get the minimum number of competitors required for this match type.
     *
     * @return int
     */
    public function getMinimumCompetitors(): int
    {
        return $this->number_of_sides ?? 2;
    }

    /**
     * Get the maximum number of competitors allowed for this match type.
     *
     * @return int
     */
    public function getMaximumCompetitors(): int
    {
        // For now, assume same as minimum unless specified otherwise
        return $this->getMinimumCompetitors();
    }
}

<?php

declare(strict_types=1);

namespace App\Models\Matches;

use Database\Factories\Matches\MatchStipulationFactory;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $name
 * @property string $slug
 * @property string|null $description
 * @property bool $is_active
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, EventMatch> $eventMatches
 *
 * @method static \Database\Factories\Matches\MatchStipulationFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MatchStipulation newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MatchStipulation newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MatchStipulation query()
 *
 * @mixin \Eloquent
 */
#[UseFactory(MatchStipulationFactory::class)]
class MatchStipulation extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'matches_stipulations';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'slug',
        'description',
        'is_active',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    /**
     * The model's default values for attributes.
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'is_active' => true,
    ];

    /**
     * Get all event matches that use this stipulation.
     *
     * @return HasMany<EventMatch, $this>
     */
    public function eventMatches(): HasMany
    {
        return $this->hasMany(EventMatch::class, 'match_stipulation_id');
    }

    /**
     * Check if this is a standard wrestling match (no special stipulation).
     */
    public function isStandardMatch(): bool
    {
        return $this->slug === 'standard';
    }

    /**
     * Check if this stipulation requires special rules or equipment.
     */
    public function requiresSpecialSetup(): bool
    {
        $specialStipulations = [
            'steel_cage',
            'ladder_match',
            'tlc_match',
            'hell_in_a_cell',
            'elimination_chamber',
        ];

        return in_array($this->slug, $specialStipulations, true);
    }

    /**
     * Check if this stipulation typically involves weapons or hardcore elements.
     */
    public function isHardcoreStipulation(): bool
    {
        $hardcoreStipulations = [
            'no_dq',
            'hardcore_match',
            'tlc_match',
            'extreme_rules',
            'street_fight',
            'falls_count_anywhere',
        ];

        return in_array($this->slug, $hardcoreStipulations, true);
    }

    /**
     * Check if this stipulation has special elimination rules.
     */
    public function hasEliminationRules(): bool
    {
        $eliminationStipulations = [
            'elimination_chamber',
            'survivor_series',
        ];

        return in_array($this->slug, $eliminationStipulations, true);
    }

    /**
     * Get the formatted display name for the stipulation.
     */
    public function getDisplayName(): string
    {
        return $this->name;
    }

    /**
     * Get the match preview text for this stipulation.
     */
    public function getMatchPreview(): string
    {
        return $this->name . ' Match';
    }
}
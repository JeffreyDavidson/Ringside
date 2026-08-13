<?php

declare(strict_types=1);

namespace App\Models\Matches;

use Database\Factories\Matches\MatchStipulationFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Collection;
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
 *
 * @property-read Collection<int, EventMatch> $eventMatches
 *
 * @method static \Database\Factories\Matches\MatchStipulationFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MatchStipulation newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MatchStipulation newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MatchStipulation query()
 *
 * @mixin \Eloquent
 */
#[Table('matches_stipulations')]
#[Fillable('name', 'slug', 'description', 'is_active')]
#[UseFactory(MatchStipulationFactory::class)]
class MatchStipulation extends Model
{
    /** @use HasFactory<MatchStipulationFactory> */
    use HasFactory;

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
}

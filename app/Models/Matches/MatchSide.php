<?php

declare(strict_types=1);

namespace App\Models\Matches;

use App\Builders\Matches\MatchSideBuilder;
use Database\Factories\Matches\MatchSideFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Attributes\UseEloquentBuilder;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $match_id
 * @property int $position
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 *
 * @property-read EventMatch $match
 * @property-read Collection<int, MatchCompetitor> $competitors
 *
 * @method static MatchSideFactory factory($count = null, $state = [])
 *
 * @mixin \Eloquent
 */
#[Table('events_matches_sides')]
#[Fillable('match_id', 'position')]
#[UseFactory(MatchSideFactory::class)]
#[UseEloquentBuilder(MatchSideBuilder::class)]
class MatchSide extends Model
{
    /** @use HasFactory<MatchSideFactory> */
    use HasFactory;

    /** @return BelongsTo<EventMatch, $this> */
    public function match(): BelongsTo
    {
        return $this->belongsTo(EventMatch::class, 'match_id');
    }

    /** @return HasMany<MatchCompetitor, $this> */
    public function competitors(): HasMany
    {
        return $this->hasMany(MatchCompetitor::class, 'match_side_id');
    }
}

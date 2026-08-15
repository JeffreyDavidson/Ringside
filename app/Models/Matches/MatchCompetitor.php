<?php

declare(strict_types=1);

namespace App\Models\Matches;

use App\Builders\Matches\MatchCompetitorBuilder;
use App\Collections\MatchCompetitorsCollection;
use App\Models\TagTeams\TagTeam;
use App\Models\Wrestlers\Wrestler;
use Database\Factories\Matches\MatchCompetitorFactory;
use Illuminate\Database\Eloquent\Attributes\CollectedBy;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\UseEloquentBuilder;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphPivot;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $match_id
 * @property string $competitor_type
 * @property int $competitor_id
 * @property int $match_side_id
 * @property int|null $entry_order
 * @property int|null $elimination_order
 * @property int|null $eliminated_by_match_competitor_id
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 *
 * @property-read Wrestler|TagTeam $competitor
 * @property-read MatchSide $side
 * @property-read MatchCompetitor|null $eliminatedBy
 * @property-read Collection<int, MatchCompetitor> $eliminations
 *
 * @method static MatchCompetitorsCollection<int, static> all($columns = ['*'])
 * @method static MatchCompetitorsCollection<int, static> get($columns = ['*'])
 * @method static \Database\Factories\Matches\MatchCompetitorFactory factory($count = null, $state = [])
 * @method static MatchCompetitorBuilder<static>|MatchCompetitor forCompetitorIds(string $competitorType, \Illuminate\Support\Collection<int, int> $competitorIds)
 * @method static MatchCompetitorBuilder<static>|MatchCompetitor forEventIds(\Illuminate\Support\Collection<int, int> $eventIds)
 * @method static MatchCompetitorBuilder<static>|MatchCompetitor newModelQuery()
 * @method static MatchCompetitorBuilder<static>|MatchCompetitor newQuery()
 * @method static MatchCompetitorBuilder<static>|MatchCompetitor query()
 *
 * @mixin \Eloquent
 */
#[CollectedBy(MatchCompetitorsCollection::class)]
#[Fillable('match_id', 'match_side_id', 'competitor_id', 'competitor_type')]
#[UseEloquentBuilder(MatchCompetitorBuilder::class)]
#[UseFactory(MatchCompetitorFactory::class)]
class MatchCompetitor extends MorphPivot
{
    /** @use HasFactory<MatchCompetitorFactory> */
    use HasFactory;

    public $incrementing = true;

    /**
     * @return BelongsTo<EventMatch, $this>
     */
    public function eventMatch(): BelongsTo
    {
        return $this->belongsTo(EventMatch::class, 'match_id');
    }

    /** @return BelongsTo<MatchSide, $this> */
    public function side(): BelongsTo
    {
        return $this->belongsTo(MatchSide::class, 'match_side_id');
    }

    /** @return BelongsTo<MatchCompetitor, $this> */
    public function eliminatedBy(): BelongsTo
    {
        return $this->belongsTo(self::class, 'eliminated_by_match_competitor_id');
    }

    /** @return HasMany<MatchCompetitor, $this> */
    public function eliminations(): HasMany
    {
        return $this->hasMany(self::class, 'eliminated_by_match_competitor_id');
    }

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'events_matches_competitors';

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'entry_order' => 'integer',
            'elimination_order' => 'integer',
            'eliminated_by_match_competitor_id' => 'integer',
        ];
    }

    /**
     * Get the competitor for the match (Wrestler or TagTeam).
     *
     * @return MorphTo<Model, $this>
     */
    public function competitor(): MorphTo
    {
        return $this->morphTo();
    }
}

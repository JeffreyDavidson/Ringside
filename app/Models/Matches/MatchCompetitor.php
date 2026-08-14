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
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphPivot;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $match_id
 * @property string $competitor_type
 * @property int $competitor_id
 * @property int $side_number Numeric identifier for the side/team this competitor belongs to. Used to group competitors by side.
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 *
 * @property-read Wrestler|TagTeam $competitor
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
#[Fillable('match_id', 'competitor_id', 'competitor_type', 'side_number')]
#[UseEloquentBuilder(MatchCompetitorBuilder::class)]
#[UseFactory(MatchCompetitorFactory::class)]
class MatchCompetitor extends MorphPivot
{
    /** @use HasFactory<MatchCompetitorFactory> */
    use HasFactory;

    /**
     * @return BelongsTo<EventMatch, $this>
     */
    public function eventMatch(): BelongsTo
    {
        return $this->belongsTo(EventMatch::class, 'match_id');
    }

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'events_matches_competitors';

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

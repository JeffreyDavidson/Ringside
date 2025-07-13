<?php

declare(strict_types=1);

namespace App\Models\Matches;

use App\Collections\EventMatchCompetitorsCollection;
use App\Models\TagTeams\TagTeam;
use App\Models\Wrestlers\Wrestler;
use Database\Factories\Matches\EventMatchCompetitorFactory;
use Illuminate\Database\Eloquent\Attributes\CollectedBy;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphPivot;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $event_match_id
 * @property string $competitor_type
 * @property int $competitor_id
 * @property int $side_number Numeric identifier for the side/team this competitor belongs to. Used to group competitors by side.
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Wrestler|TagTeam $competitor
 *
 * @method static EventMatchCompetitorsCollection<int, static> all($columns = ['*'])
 * @method static EventMatchCompetitorsCollection<int, static> get($columns = ['*'])
 * @method static \Database\Factories\Matches\EventMatchCompetitorFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EventMatchCompetitor newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EventMatchCompetitor newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EventMatchCompetitor query()
 *
 * @mixin \Eloquent
 */
#[CollectedBy(EventMatchCompetitorsCollection::class)]
#[UseFactory(EventMatchCompetitorFactory::class)]
class EventMatchCompetitor extends MorphPivot
{
    use HasFactory;
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'events_matches_competitors';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'event_match_id',
        'competitor_id',
        'competitor_type',
        'side_number',
    ];

    /**
     * Get the competitor for the match (Wrestler or TagTeam).
     *
     * @return MorphTo<Model, $this>
     */
    public function competitor(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Get the resolved competitor instance (must be Wrestler or TagTeam).
     */
    public function getCompetitor(): Wrestler|TagTeam
    {
        /** @var Wrestler|TagTeam $competitor */
        $competitor = $this->competitor;

        return $competitor;
    }
}

<?php

declare(strict_types=1);

namespace App\Models;

use App\Collections\EventMatchCompetitorsCollection;
use Exception;
use Illuminate\Database\Eloquent\Attributes\CollectedBy;
use Illuminate\Database\Eloquent\Relations\MorphPivot;
use Illuminate\Database\Eloquent\Relations\MorphTo;

#[CollectedBy(EventMatchCompetitorsCollection::class)]
/**
 * @property int $id
 * @property int $event_match_id
 * @property string $competitor_type
 * @property int $competitor_id
 * @property int $side_number
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read MorphTo<Wrestler|TagTeam> $competitor
 *
 * @method static EventMatchCompetitorsCollection<int, static> all($columns = ['*'])
 * @method static EventMatchCompetitorsCollection<int, static> get($columns = ['*'])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EventMatchCompetitor newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EventMatchCompetitor newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EventMatchCompetitor query()
 *
 * @mixin \Eloquent
 */
final class EventMatchCompetitor extends MorphPivot
{
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
     * Retrieve the previous champion of the title championship.
     *
     * @return MorphTo<\Illuminate\Database\Eloquent\Model, $this>
     */
    public function competitor(): MorphTo
    {
        return $this->morphTo(__FUNCTION__, 'competitor_type', 'competitor_id');
    }

    public function getCompetitor(): Wrestler|TagTeam
    {
        $competitor = $this->competitor;

        if (! is_object($competitor)) {
            throw new Exception('No popularized object');
        }

        return match ($competitor::class) {
            Wrestler::class,
            TagTeam::class => $competitor,
            default => throw new Exception('Unexpected relation: '.$competitor::class),
        };
    }
}

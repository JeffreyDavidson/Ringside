<?php

declare(strict_types=1);

namespace App\Models;

use App\Collections\EventMatchCompetitorsCollection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphPivot;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class EventMatchCompetitor extends MorphPivot
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'event_match_competitors';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'event_match_id',
        'competitor_id',
        'competitor_type',
        'side_number',
    ];

    /**
     * Retrieve the model as the competitor.
     *
     * @return MorphTo<Model, EventMatchCompetitor>
     */
    public function competitor(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Create a new Eloquent Collection instance.
     *
     * @param  array<int, EventMatchCompetitor>  $models
     * @return EventMatchCompetitorsCollection<array-key, Model>
     */
    public function newCollection(array $models = []): EventMatchCompetitorsCollection
    {
        return new EventMatchCompetitorsCollection($models);
    }
}

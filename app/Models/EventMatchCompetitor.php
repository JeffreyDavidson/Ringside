<?php

declare(strict_types=1);

namespace App\Models;

use App\Collections\EventMatchCompetitorsCollection;
use Illuminate\Database\Eloquent\Attributes\CollectedBy;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphPivot;
use Illuminate\Database\Eloquent\Relations\MorphTo;

#[CollectedBy(EventMatchCompetitorsCollection::class)]
class EventMatchCompetitor extends MorphPivot
{
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
     * Retrieve the previous champion of the title championship.
     *
     * @return MorphTo<Model, EventMatchCompetitor>
     */
    public function competitor(): MorphTo
    {
        return $this->morphTo(__FUNCTION__, 'competitor_type', 'competitor_id');
    }
}

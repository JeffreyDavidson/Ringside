<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class EventMatchResult extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'events_matches_results';

    /**
     * Get the winner of the event match.
     *
     * @return MorphTo<Model, $this>
     */
    public function winner(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Get the decision of the end of the event match.
     *
     * @return BelongsTo<MatchDecision, $this>
     */
    public function decision(): BelongsTo
    {
        return $this->belongsTo(MatchDecision::class, 'match_decision_id');
    }
}

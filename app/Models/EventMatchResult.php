<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class EventMatchResult extends Model
{
    /**
     * Get the winner of the event match.
     *
     * @return MorphTo<Model, Model>
     */
    public function winner(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Get the decision of the end of the event match.
     *
     * @return BelongsTo<MatchDecision, EventMatchResult>
     */
    public function decision(): BelongsTo
    {
        return $this->belongsTo(MatchDecision::class, 'match_decision_id');
    }
}

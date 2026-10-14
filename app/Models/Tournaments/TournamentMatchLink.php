<?php

declare(strict_types=1);

namespace App\Models\Tournaments;

use App\Models\Concerns\BelongsToPromotion;
use App\Models\Matches\EventMatch;
use App\Models\Scopes\PromotionContextScope;
use Database\Factories\Tournaments\TournamentMatchLinkFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $promotion_id
 * @property int $tournament_id
 * @property int $node_id
 * @property ?int $active_node_id
 * @property int $match_id
 * @property int $attempt
 * @property-read Tournament $tournament
 * @property-read TournamentNode $node
 * @property-read EventMatch $match
 */
#[Fillable([])]
#[UseFactory(TournamentMatchLinkFactory::class)]
class TournamentMatchLink extends Model
{
    use BelongsToPromotion;

    /** @use HasFactory<TournamentMatchLinkFactory> */
    use HasFactory;

    /** @return BelongsTo<Tournament, $this> */
    public function tournament(): BelongsTo
    {
        return $this->belongsTo(Tournament::class);
    }

    /** @return BelongsTo<TournamentNode, $this> */
    public function node(): BelongsTo
    {
        return $this->belongsTo(TournamentNode::class, 'node_id');
    }

    /** @return BelongsTo<EventMatch, $this> */
    public function match(): BelongsTo
    {
        return $this->belongsTo(EventMatch::class, 'match_id')->withTrashed()->withoutGlobalScope(PromotionContextScope::class);
    }
}

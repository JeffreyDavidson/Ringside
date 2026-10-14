<?php

declare(strict_types=1);

namespace App\Models\Tournaments;

use App\Models\Concerns\BelongsToPromotion;
use App\Models\Events\Event;
use Database\Factories\Tournaments\TournamentNodeFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property int $promotion_id
 * @property int $tournament_id
 * @property int $round
 * @property int $position
 * @property ?int $first_seed
 * @property ?int $second_seed
 * @property ?int $planned_event_id
 * @property ?int $walkover_entry_id
 * @property ?string $walkover_reason
 * @property-read Tournament $tournament
 * @property-read ?Event $plannedEvent
 * @property-read Collection<int, TournamentMatchLink> $links
 */
#[Fillable([])]
#[UseFactory(TournamentNodeFactory::class)]
class TournamentNode extends Model
{
    use BelongsToPromotion;

    /** @use HasFactory<TournamentNodeFactory> */
    use HasFactory;

    /** @return BelongsTo<Tournament, $this> */
    public function tournament(): BelongsTo
    {
        return $this->belongsTo(Tournament::class);
    }

    /** @return BelongsTo<Event, $this> */
    public function plannedEvent(): BelongsTo
    {
        return $this->belongsTo(Event::class, 'planned_event_id')->withTrashed();
    }

    /** @return HasMany<TournamentMatchLink, $this> */
    public function links(): HasMany
    {
        return $this->hasMany(TournamentMatchLink::class, 'node_id')->orderBy('attempt');
    }
}

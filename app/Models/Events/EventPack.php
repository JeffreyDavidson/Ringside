<?php

declare(strict_types=1);

namespace App\Models\Events;

use App\Builders\Events\EventPackBuilder;
use Database\Factories\Events\EventPackFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\UseEloquentBuilder;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Override;

/**
 * @phpstan-type Assignment array{id: string, contact_id: int, name: string, role: string, station: ?string, call_at: ?string, timezone: string, offset: ?string, setup: ?string, during: ?string, strike: ?string, active: bool}
 * @phpstan-type Cue array{id: string, item_key: string, subject: string, trigger: string, instruction: string, role: ?string, owner_id: ?string, owner_name: ?string, owner_station: ?string, owner_role: ?string, owner_contact_id: ?int, pronunciation: ?string, track: ?string, url: ?string}
 * @phpstan-type Production array{notes: ?string, assignments: list<Assignment>, cues: list<Cue>}
 * @phpstan-type Timing array{planned_at: ?string, revised_at: ?string, minutes: ?int, notes: ?string}
 * @phpstan-type MatchEntry array{match_id: int, planned_at: ?string, revised_at: ?string, minutes: ?int, notes: ?string}
 * @phpstan-type Segment array{id: string, label: string, after_match_id: ?int, planned_at: ?string, revised_at: ?string, minutes: ?int, notes: ?string}
 * @phpstan-type SponsorTask array{id: string, label: string, instruction: string, owner: ?string}
 * @phpstan-type Content array{timezone: string, doors_at: ?string, revision: ?string, master: ?string, notes: ?string, follow_up: ?string, matches: list<MatchEntry>, segments: list<Segment>, sponsor_tasks?: list<SponsorTask>, production?: Production}
 *
 * @property int $id
 * @property int $event_id
 * @property int $version
 * @property string $card_fingerprint
 * @property Content $content
 * @property Carbon $updated_at
 * @property-read Event $event
 */
#[Fillable('content')]
#[UseFactory(EventPackFactory::class)]
#[UseEloquentBuilder(EventPackBuilder::class)]
class EventPack extends Model
{
    /** @use HasFactory<EventPackFactory> */
    use HasFactory;

    #[Override]
    protected function casts(): array
    {
        return [
            'version' => 'integer',
            'content' => 'array',
        ];
    }

    /** @return BelongsTo<Event, $this> */
    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }
}

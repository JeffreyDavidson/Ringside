<?php

declare(strict_types=1);

namespace App\Models\Events;

use App\Builders\Events\EventLiveRunBuilder;
use App\Enums\Events\LiveShowStatus;
use Database\Factories\Events\EventLiveRunFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\UseEloquentBuilder;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @phpstan-import-type Assignment from \App\Models\Events\EventPack
 * @phpstan-import-type Cue from \App\Models\Events\EventPack
 *
 * @phpstan-type Timing array{planned_at?: ?string, revised_at?: ?string, minutes?: ?int, notes?: ?string}
 * @phpstan-type Item array{key: string, label: string, details: string, referees: string, warning: bool, timing: Timing, timezone: string, state: string, start: ?string, finish: ?string, reason: ?string, updated_by: ?int, cues?: list<Cue>}
 * @phpstan-type Person array{key: string, name: string, role: string, matches: list<int>, warning: bool, assigned: bool, arrived: ?string, briefed: ?string, updated_by: ?int, assignments?: list<Assignment>}
 * @phpstan-type Content array{event_date: string, event_name: string, venue: ?string, timezone: string, revision: ?string, items: list<Item>, roster: list<Person>, production?: array{assignments: list<Assignment>}}
 *
 * @property int $id
 * @property int $event_id
 * @property int $version
 * @property int $pack_version
 * @property string $card_fingerprint
 * @property LiveShowStatus $status
 * @property Content $content
 * @property Carbon $prepared_at
 * @property ?Carbon $completed_at
 * @property Carbon $updated_at
 * @property-read Event $event
 */
#[Fillable([])]
#[UseFactory(EventLiveRunFactory::class)]
#[UseEloquentBuilder(EventLiveRunBuilder::class)]
class EventLiveRun extends Model
{
    /** @use HasFactory<EventLiveRunFactory> */
    use HasFactory;

    #[\Override]
    protected function casts(): array
    {
        return [
            'version' => 'integer',
            'pack_version' => 'integer',
            'status' => LiveShowStatus::class,
            'content' => 'array',
            'prepared_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<Event, $this> */
    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }
}

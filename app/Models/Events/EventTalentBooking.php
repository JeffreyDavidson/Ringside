<?php

declare(strict_types=1);

namespace App\Models\Events;

use App\Enums\Events\TalentBookingStatus;
use Database\Factories\Events\EventTalentBookingFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @phpstan-type Terms array{fee_minor: ?int, arrival_at: ?string, arrival_timezone: string, travel_notes: ?string}
 * @phpstan-type Agreement array{context: array<string, mixed>, on_card: bool, terms: Terms, confirmed_at: string}
 *
 * @property int $id
 * @property int $event_id
 * @property string $person_type
 * @property int $person_id
 * @property string $person_name
 * @property string $person_role
 * @property TalentBookingStatus $status
 * @property int $version
 * @property ?int $fee_minor
 * @property string $currency
 * @property ?Carbon $arrival_at
 * @property string $arrival_timezone
 * @property ?string $travel_notes
 * @property bool $terms_need_review
 * @property ?Agreement $confirmed_snapshot
 * @property-read Event $event
 * @property-read Collection<int, EventTalentBookingRevision> $revisions
 */
#[Fillable([])]
#[UseFactory(EventTalentBookingFactory::class)]
class EventTalentBooking extends Model
{
    /** @use HasFactory<EventTalentBookingFactory> */
    use HasFactory;

    /** @return array<string, string> */
    protected function casts(): array
    {
        return ['status' => TalentBookingStatus::class, 'version' => 'integer', 'person_id' => 'integer',
            'fee_minor' => 'integer', 'arrival_at' => 'datetime', 'terms_need_review' => 'boolean', 'confirmed_snapshot' => 'array'];
    }

    /** @return BelongsTo<Event, $this> */
    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    /** @return HasMany<EventTalentBookingRevision, $this> */
    public function revisions(): HasMany
    {
        return $this->hasMany(EventTalentBookingRevision::class, 'booking_id');
    }
}

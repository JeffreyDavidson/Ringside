<?php

declare(strict_types=1);

namespace App\Models\Events;

use App\Observers\Events\TalentBookingRevisionObserver;
use Database\Factories\Events\EventTalentBookingRevisionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Override;

/**
 * @property int $id
 * @property int $booking_id
 * @property int $version
 * @property string $operation
 * @property ?string $reason
 * @property array<string, mixed> $snapshot
 * @property ?int $actor_id
 * @property string $actor_name
 * @property Carbon $created_at
 * @property-read EventTalentBooking $booking
 */
#[Fillable([])]
#[ObservedBy(TalentBookingRevisionObserver::class)]
#[UseFactory(EventTalentBookingRevisionFactory::class)]
class EventTalentBookingRevision extends Model
{
    /** @use HasFactory<EventTalentBookingRevisionFactory> */
    use HasFactory;

    #[Override]
    protected function casts(): array
    {
        return [
            'version' => 'integer',
            'snapshot' => 'array',
        ];
    }

    /** @return BelongsTo<EventTalentBooking, $this> */
    public function booking(): BelongsTo
    {
        return $this->belongsTo(EventTalentBooking::class, 'booking_id');
    }
}

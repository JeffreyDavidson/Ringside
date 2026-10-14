<?php

declare(strict_types=1);

namespace App\Models\Events;

use App\Enums\Events\SponsorCommitmentStatus;
use App\Enums\Events\SponsorCommitmentType;
use App\Models\Concerns\BelongsToPromotion;
use App\Models\Sponsors\Sponsor;
use Database\Factories\Events\EventSponsorCommitmentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $promotion_id
 * @property int $event_id
 * @property int $sponsor_id
 * @property int $version
 * @property string $submission_key
 * @property string $sponsor_name
 * @property SponsorCommitmentType $type
 * @property SponsorCommitmentStatus $status
 * @property string $description
 * @property ?string $responsible
 * @property ?Carbon $deadline_at
 * @property string $timezone
 * @property ?string $notes
 * @property ?string $agreement_reference
 * @property ?string $evidence_note
 * @property ?string $evidence_url
 * @property ?Carbon $completed_at
 * @property ?string $instruction_label
 * @property ?string $instruction
 * @property ?string $instruction_owner
 * @property ?string $pack_key
 * @property ?string $pack_instruction_hash
 * @property array<string, mixed> $event_context
 * @property-read Event $event
 * @property-read Sponsor $sponsor
 */
#[Fillable([])]
#[UseFactory(EventSponsorCommitmentFactory::class)]
class EventSponsorCommitment extends Model
{
    use BelongsToPromotion;

    /** @use HasFactory<EventSponsorCommitmentFactory> */
    use HasFactory;

    /** @return array<string, string> */
    protected function casts(): array
    {
        return ['version' => 'integer', 'type' => SponsorCommitmentType::class, 'status' => SponsorCommitmentStatus::class,
            'deadline_at' => 'datetime', 'completed_at' => 'datetime', 'event_context' => 'array'];
    }

    /** @return BelongsTo<Event, $this> */
    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class)->withTrashed();
    }

    /** @return BelongsTo<Sponsor, $this> */
    public function sponsor(): BelongsTo
    {
        return $this->belongsTo(Sponsor::class);
    }

    /** @return HasMany<EventSponsorCommitmentRevision, $this> */
    public function revisions(): HasMany
    {
        return $this->hasMany(EventSponsorCommitmentRevision::class, 'commitment_id');
    }
}

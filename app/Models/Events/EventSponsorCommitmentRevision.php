<?php

declare(strict_types=1);

namespace App\Models\Events;

use App\Models\Concerns\BelongsToPromotion;
use App\Observers\Events\SponsorCommitmentRevisionObserver;
use Database\Factories\Events\EventSponsorCommitmentRevisionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $promotion_id
 * @property int $commitment_id
 * @property int $version
 * @property ?int $actor_id
 * @property string $actor_name
 * @property string $operation
 * @property ?string $reason
 * @property array<string, mixed> $snapshot
 * @property Carbon $created_at
 */
#[Fillable([])]
#[ObservedBy(SponsorCommitmentRevisionObserver::class)]
#[UseFactory(EventSponsorCommitmentRevisionFactory::class)]
class EventSponsorCommitmentRevision extends Model
{
    use BelongsToPromotion;

    /** @use HasFactory<EventSponsorCommitmentRevisionFactory> */
    use HasFactory;

    #[\Override]
    protected function casts(): array
    {
        return [
            'version' => 'integer',
            'snapshot' => 'array',
        ];
    }

    /** @return BelongsTo<EventSponsorCommitment, $this> */
    public function commitment(): BelongsTo
    {
        return $this->belongsTo(EventSponsorCommitment::class, 'commitment_id');
    }
}

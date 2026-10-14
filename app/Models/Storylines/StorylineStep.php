<?php

declare(strict_types=1);

namespace App\Models\Storylines;

use App\Enums\Storylines\StepStatus;
use App\Models\Concerns\BelongsToPromotion;
use App\Models\Events\Event;
use App\Models\Matches\EventMatch;
use Database\Factories\Storylines\StorylineStepFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $storyline_id
 * @property int $promotion_id
 * @property string $heading
 * @property string $description
 * @property StepStatus $status
 * @property int $sequence
 * @property ?int $event_id
 * @property ?int $match_id
 * @property ?string $segment_id
 * @property ?Carbon $target_on
 * @property ?Carbon $target_at
 * @property string $timezone
 * @property array<string, mixed> $source_context
 * @property int $version
 * @property string $submission_id
 * @property-read Event|null $event
 * @property-read EventMatch|null $match
 * @property-read Storyline $storyline
 */
#[Fillable([])]
#[UseFactory(StorylineStepFactory::class)]
class StorylineStep extends Model
{
    use BelongsToPromotion;

    /** @use HasFactory<StorylineStepFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return ['status' => StepStatus::class, 'target_on' => 'date', 'target_at' => 'datetime', 'source_context' => 'array', 'sequence' => 'integer', 'version' => 'integer'];
    }

    /** @return BelongsTo<Storyline, $this> */
    public function storyline(): BelongsTo
    {
        return $this->belongsTo(Storyline::class);
    }

    /** @return BelongsTo<Event, $this> */
    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class)->withTrashed();
    }

    /** @return BelongsTo<EventMatch, $this> */
    public function match(): BelongsTo
    {
        return $this->belongsTo(EventMatch::class, 'match_id')->withTrashed();
    }
}

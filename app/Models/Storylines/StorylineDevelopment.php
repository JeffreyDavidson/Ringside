<?php

declare(strict_types=1);

namespace App\Models\Storylines;

use App\Models\Concerns\BelongsToPromotion;
use App\Models\Events\Event;
use App\Models\Matches\EventMatch;
use Database\Factories\Storylines\StorylineDevelopmentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Override;

/**
 * @property int $id
 * @property int $storyline_id
 * @property int $promotion_id
 * @property ?int $storyline_step_id
 * @property string $heading
 * @property string $description
 * @property Carbon $occurred_on
 * @property string $timezone
 * @property int $sequence
 * @property ?int $event_id
 * @property ?int $match_id
 * @property ?string $segment_id
 * @property array<string, mixed> $source_context
 * @property list<array{type: string, id: int, label: string}> $cast
 * @property int $version
 * @property string $submission_id
 * @property ?Carbon $retracted_at
 * @property-read Event|null $event
 * @property-read EventMatch|null $match
 * @property-read Storyline $storyline
 */
#[Fillable(['heading', 'description', 'occurred_on', 'sequence'])]
#[UseFactory(StorylineDevelopmentFactory::class)]
class StorylineDevelopment extends Model
{
    use BelongsToPromotion;

    /** @use HasFactory<StorylineDevelopmentFactory> */
    use HasFactory;

    #[Override]
    protected function casts(): array
    {
        return [
            'occurred_on' => 'date',
            'source_context' => 'array',
            'cast' => 'array',
            'sequence' => 'integer',
            'version' => 'integer',
            'retracted_at' => 'datetime',
        ];
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

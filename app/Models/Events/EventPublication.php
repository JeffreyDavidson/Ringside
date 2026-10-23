<?php

declare(strict_types=1);

namespace App\Models\Events;

use App\Builders\Events\EventPublicationBuilder;
use App\Models\Concerns\BelongsToPromotion;
use App\Models\Users\User;
use Database\Factories\Events\EventPublicationFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\UseEloquentBuilder;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Override;

/**
 * @phpstan-type Selection array{id: int, type: bool, stipulation: bool, titles: bool}
 * @phpstan-type Poster array{key: string, alt: string, caption: ?string}
 * @phpstan-type Row array{label: string, details: string, outcome: ?string}
 * @phpstan-type Card array{title: string, promotion: string, announcement: string, summary: ?string, notice: ?string, date: ?string, timezone: string, venue: ?string, address: ?string, doors: ?string, tickets: ?string, poster: ?Poster, rows: list<Row>}
 * @phpstan-type Draft array{announcement: string, summary: ?string, notice: ?string, address: bool, doors_at: ?string, doors_offset: ?string, tickets: ?string, poster: ?Poster, matches: list<Selection>, results: list<int>}
 * @phpstan-type Results array{rows: list<Row>, corrected_at: ?string}
 *
 * @property int $id
 * @property int $event_id
 * @property int $promotion_id
 * @property string $public_id
 * @property string $slug
 * @property int $version
 * @property Draft $draft
 * @property Card|null $card
 * @property Results|null $results
 * @property list<Selection>|null $card_source
 * @property list<int>|null $results_source
 * @property string|null $schedule_fingerprint
 * @property string|null $card_fingerprint
 * @property string|null $results_fingerprint
 * @property Carbon|null $published_at
 * @property Carbon|null $results_published_at
 * @property Carbon|null $unpublished_at
 * @property int|null $published_by
 * @property int|null $results_published_by
 * @property-read Event $event
 * @property-read User|null $publisher
 * @property-read User|null $resultsPublisher
 */
#[Fillable('draft')]
#[UseFactory(EventPublicationFactory::class)]
#[UseEloquentBuilder(EventPublicationBuilder::class)]
class EventPublication extends Model
{
    use BelongsToPromotion;

    /** @use HasFactory<EventPublicationFactory> */
    use HasFactory;

    #[Override]
    protected function casts(): array
    {
        return [
            'version' => 'integer',
            'draft' => 'array',
            'card' => 'array',
            'results' => 'array',
            'card_source' => 'array',
            'results_source' => 'array',
            'published_at' => 'datetime',
            'results_published_at' => 'datetime',
            'unpublished_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<User, $this> */
    public function publisher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'published_by');
    }

    /** @return BelongsTo<User, $this> */
    public function resultsPublisher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'results_published_by');
    }

    /** @return BelongsTo<Event, $this> */
    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }
}

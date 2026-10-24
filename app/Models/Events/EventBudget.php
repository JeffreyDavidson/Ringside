<?php

declare(strict_types=1);

namespace App\Models\Events;

use App\Builders\Events\EventBudgetBuilder;
use App\Collections\EventBudgetLineCollection;
use Database\Factories\Events\EventBudgetFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\UseEloquentBuilder;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Override;

/**
 * @property int $id
 * @property int $event_id
 * @property string $currency
 * @property string $timezone
 * @property int $version
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property-read Event $event
 * @property-read EventBudgetLineCollection $lines
 * @property-read Collection<int, EventBudgetRevision> $revisions
 */
#[Fillable([])]
#[UseFactory(EventBudgetFactory::class)]
#[UseEloquentBuilder(EventBudgetBuilder::class)]
class EventBudget extends Model
{
    /** @use HasFactory<EventBudgetFactory> */
    use HasFactory;

    #[Override]
    protected function casts(): array
    {
        return [
            'version' => 'integer',
        ];
    }

    /** @return BelongsTo<Event, $this> */
    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    /** @return HasMany<EventBudgetLine, $this> */
    public function lines(): HasMany
    {
        return $this->hasMany(EventBudgetLine::class)->orderBy('id');
    }

    /** @return HasMany<EventBudgetRevision, $this> */
    public function revisions(): HasMany
    {
        return $this->hasMany(EventBudgetRevision::class);
    }
}

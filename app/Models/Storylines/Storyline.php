<?php

declare(strict_types=1);

namespace App\Models\Storylines;

use App\Builders\Storylines\StorylineBuilder;
use App\Enums\Storylines\StorylineStatus;
use App\Models\Concerns\BelongsToPromotion;
use App\Policies\StorylinePolicy;
use Database\Factories\Storylines\StorylineFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\UseEloquentBuilder;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Attributes\UsePolicy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * @property int $id
 * @property int $promotion_id
 * @property string $title
 * @property string $premise
 * @property ?string $payoff
 * @property StorylineStatus $status
 * @property ?StorylineStatus $previous_status
 * @property-read ?StorylineStep $nextStep
 * @property-read ?StorylineDevelopment $lastDevelopment
 * @property int $version
 */
#[Fillable('title', 'premise', 'payoff')]
#[UseFactory(StorylineFactory::class)]
#[UseEloquentBuilder(StorylineBuilder::class)]
#[UsePolicy(StorylinePolicy::class)]
class Storyline extends Model
{
    use BelongsToPromotion;

    /** @use HasFactory<StorylineFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return ['status' => StorylineStatus::class, 'previous_status' => StorylineStatus::class, 'version' => 'integer'];
    }

    /** @return BelongsTo<StorylineStep, $this> */
    public function nextStep(): BelongsTo
    {
        return $this->belongsTo(StorylineStep::class, 'next_step_id');
    }

    /** @return HasOne<StorylineDevelopment, $this> */
    public function lastDevelopment(): HasOne
    {
        return $this->hasOne(StorylineDevelopment::class)->ofMany(['occurred_on' => 'max', 'sequence' => 'max', 'id' => 'max'], fn (Builder $query) => $query->whereNull('retracted_at'));
    }

    /** @return HasMany<StorylineParticipant, $this> */
    public function participants(): HasMany
    {
        return $this->hasMany(StorylineParticipant::class);
    }

    /** @return HasMany<StorylineStep, $this> */
    public function steps(): HasMany
    {
        return $this->hasMany(StorylineStep::class);
    }

    /** @return HasMany<StorylineDevelopment, $this> */
    public function developments(): HasMany
    {
        return $this->hasMany(StorylineDevelopment::class);
    }

    /** @return HasMany<StorylineRevision, $this> */
    public function revisions(): HasMany
    {
        return $this->hasMany(StorylineRevision::class);
    }
}

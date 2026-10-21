<?php

declare(strict_types=1);

namespace App\Models\Storylines;

use App\Enums\Storylines\StorylineRevisionOperation;
use App\Models\Concerns\BelongsToPromotion;
use App\Models\Users\User;
use Database\Factories\Storylines\StorylineRevisionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $storyline_id
 * @property int $promotion_id
 * @property int $actor_id
 * @property StorylineRevisionOperation $operation
 * @property string $record_type
 * @property int $record_id
 * @property ?string $reason
 * @property array<string, mixed> $before
 * @property array<string, mixed> $after
 * @property-read Storyline $storyline
 */
#[Fillable([])]
#[UseFactory(StorylineRevisionFactory::class)]
class StorylineRevision extends Model
{
    use BelongsToPromotion;

    /** @use HasFactory<StorylineRevisionFactory> */
    use HasFactory;

    #[\Override]
    protected function casts(): array
    {
        return [
            'operation' => StorylineRevisionOperation::class,
            'before' => 'array',
            'after' => 'array',
        ];
    }

    /** @return BelongsTo<Storyline, $this> */
    public function storyline(): BelongsTo
    {
        return $this->belongsTo(Storyline::class);
    }

    /** @return BelongsTo<User, $this> */
    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id')->withTrashed();
    }
}

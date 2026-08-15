<?php

declare(strict_types=1);

namespace App\Models\Lifecycle;

use App\Builders\Lifecycle\LifecycleTransitionBuilder;
use App\Enums\Lifecycle\LifecycleDimension;
use App\Enums\Lifecycle\LifecycleTransitionType;
use App\Models\Users\User;
use Database\Factories\Lifecycle\LifecycleTransitionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\UseEloquentBuilder;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Carbon;
use LogicException;

/**
 * @property int $id
 * @property string $subject_type
 * @property int $subject_id
 * @property LifecycleDimension $dimension
 * @property LifecycleTransitionType $transition
 * @property Carbon $effective_at
 * @property int|null $user_id
 * @property array<string, mixed>|null $context
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 *
 * @property-read Model $subject
 * @property-read User|null $user
 */
#[Fillable('subject_type', 'subject_id', 'dimension', 'transition', 'effective_at', 'user_id', 'context')]
#[UseFactory(LifecycleTransitionFactory::class)]
#[UseEloquentBuilder(LifecycleTransitionBuilder::class)]
class LifecycleTransition extends Model
{
    /** @use HasFactory<LifecycleTransitionFactory> */
    use HasFactory;

    /** @return MorphTo<Model, $this> */
    public function subject(): MorphTo
    {
        return $this->morphTo();
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'dimension' => LifecycleDimension::class,
            'transition' => LifecycleTransitionType::class,
            'effective_at' => 'datetime',
            'context' => 'array',
        ];
    }

    protected static function booted(): void
    {
        static::updating(function (): never {
            throw new LogicException('Lifecycle transition records are immutable.');
        });

        static::deleting(function (): never {
            throw new LogicException('Lifecycle transition records are immutable.');
        });
    }
}

<?php

declare(strict_types=1);

namespace App\Models\Lifecycle;

use App\Builders\Lifecycle\LifecyclePeriodBuilder;
use Database\Factories\Lifecycle\SuspensionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\UseEloquentBuilder;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $suspendable_id
 * @property string $suspendable_type
 * @property Carbon $started_at
 * @property Carbon|null $ended_at
 *
 * @property-read Model $suspendable
 */
#[Fillable('started_at', 'ended_at')]
#[UseFactory(SuspensionFactory::class)]
#[UseEloquentBuilder(LifecyclePeriodBuilder::class)]
class Suspension extends Model
{
    /** @use HasFactory<SuspensionFactory> */
    use HasFactory;

    /** @return MorphTo<Model, $this> */
    public function suspendable(): MorphTo
    {
        return $this->morphTo();
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'started_at' => 'datetime',
            'ended_at' => 'datetime',
        ];
    }
}

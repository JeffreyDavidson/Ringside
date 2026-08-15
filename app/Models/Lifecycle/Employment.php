<?php

declare(strict_types=1);

namespace App\Models\Lifecycle;

use App\Builders\Lifecycle\LifecyclePeriodBuilder;
use Database\Factories\Lifecycle\EmploymentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\UseEloquentBuilder;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $employable_id
 * @property string $employable_type
 * @property Carbon $started_at
 * @property Carbon|null $ended_at
 *
 * @property-read Model $employable
 */
#[Fillable('started_at', 'ended_at')]
#[UseFactory(EmploymentFactory::class)]
#[UseEloquentBuilder(LifecyclePeriodBuilder::class)]
class Employment extends Model
{
    /** @use HasFactory<EmploymentFactory> */
    use HasFactory;

    /** @return MorphTo<Model, $this> */
    public function employable(): MorphTo
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

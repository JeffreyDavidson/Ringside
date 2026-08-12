<?php

declare(strict_types=1);

namespace App\Models\Lifecycle;

use Database\Factories\Lifecycle\InjuryFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $injurable_id
 * @property string $injurable_type
 * @property Carbon $started_at
 * @property Carbon|null $ended_at
 *
 * @property-read Model $injurable
 */
#[Fillable('started_at', 'ended_at')]
#[UseFactory(InjuryFactory::class)]
class Injury extends Model
{
    /** @use HasFactory<InjuryFactory> */
    use HasFactory;

    /** @return MorphTo<Model, $this> */
    public function injurable(): MorphTo
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

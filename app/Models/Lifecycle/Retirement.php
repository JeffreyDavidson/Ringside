<?php

declare(strict_types=1);

namespace App\Models\Lifecycle;

use Database\Factories\Lifecycle\RetirementFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $retirable_id
 * @property string $retirable_type
 * @property Carbon $started_at
 * @property Carbon|null $ended_at
 *
 * @property-read Model $retirable
 */
#[Fillable('started_at', 'ended_at')]
#[UseFactory(RetirementFactory::class)]
class Retirement extends Model
{
    /** @use HasFactory<RetirementFactory> */
    use HasFactory;

    /** @return MorphTo<Model, $this> */
    public function retirable(): MorphTo
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

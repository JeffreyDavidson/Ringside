<?php

declare(strict_types=1);

namespace App\Models\Roster\Availability;

use App\Builders\Roster\UnavailabilityBuilder;
use App\Models\Concerns\BelongsToPromotion;
use Database\Factories\Roster\Availability\UnavailabilityFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Attributes\UseEloquentBuilder;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $promotion_id
 * @property string $person_type
 * @property int $person_id
 * @property string $starts_on
 * @property string $ends_on
 * @property ?string $reason
 * @property ?int $created_by
 * @property ?int $cancelled_by
 * @property ?Carbon $cancelled_at
 * @property Carbon $created_at
 * @property-read Model $person
 */
#[Fillable(['starts_on', 'ends_on', 'reason'])]
#[UseFactory(UnavailabilityFactory::class)]
#[UseEloquentBuilder(UnavailabilityBuilder::class)]
#[Table(name: 'roster_unavailabilities')]
class Unavailability extends Model
{
    use BelongsToPromotion;

    /** @use HasFactory<UnavailabilityFactory> */
    use HasFactory;

    /** @return MorphTo<Model, $this> */
    public function person(): MorphTo
    {
        return $this->morphTo();
    }

    #[\Override]
    protected function casts(): array
    {
        return [
            'cancelled_at' => 'datetime',
        ];
    }
}

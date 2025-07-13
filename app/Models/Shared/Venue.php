<?php

declare(strict_types=1);

namespace App\Models\Shared;

use App\Builders\Shared\VenueBuilder;
use App\Models\Concerns\HoldsEvents;
use App\Models\Events\Event;
use Database\Factories\Shared\VenueFactory;
use Illuminate\Database\Eloquent\Attributes\UseEloquentBuilder;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $name
 * @property string $street_address
 * @property string $city
 * @property string $state
 * @property string $zipcode
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 * @property-read Collection<int, Event> $events
 * @property-read Collection<int, Event> $previousEvents
 * @property-read Collection<int, Event> $futureEvents
 *
 * @method static \Database\Factories\Shared\VenueFactory factory($count = null, $state = [])
 * @method static VenueBuilder<static>|Venue newModelQuery()
 * @method static VenueBuilder<static>|Venue newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Venue onlyTrashed()
 * @method static VenueBuilder<static>|Venue query()
 * @method static VenueBuilder<static>|Venue withEvents()
 * @method static VenueBuilder<static>|Venue withFutureEvents()
 * @method static VenueBuilder<static>|Venue withPastEvents()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Venue withTrashed()
 * @method static VenueBuilder<static>|Venue withoutEvents()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Venue withoutTrashed()
 *
 * @mixin \Eloquent
 */
#[UseFactory(VenueFactory::class)]
#[UseEloquentBuilder(VenueBuilder::class)]
class Venue extends Model
{
    use HasFactory;
    use HoldsEvents;
    use SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'street_address',
        'city',
        'state',
        'zipcode',
    ];
}

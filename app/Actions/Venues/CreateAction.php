<?php

declare(strict_types=1);

namespace App\Actions\Venues;

use App\Data\Events\VenueData;
use App\Enums\Naming\GuardedName;
use App\Exceptions\Venues\NameTakenException;
use App\Lifecycle\Naming\RecordNameLock;
use App\Models\Events\Venue;
use Illuminate\Support\Facades\DB;

class CreateAction
{
    public function __construct(private readonly RecordNameLock $nameLock) {}

    /**
     * Create a venue.
     *
     * This handles the complete venue creation workflow:
     * - Rejects a name another venue already uses (deleted ones count, as they do for the form rule); venue names are
     *   unique across all promotions, and nothing in the database keeps them unique, so it first takes the name lock
     * - Creates the venue record with location and facility details
     * - Establishes the venue as available for event hosting
     * - Sets up the foundation for future event bookings
     *
     * @param  VenueData  $venueData  The data transfer object containing venue information
     * @return Venue The newly created venue instance
     *
     * @throws NameTakenException When another venue already has the name
     */
    public function handle(VenueData $venueData): Venue
    {
        return DB::transaction(function () use ($venueData): Venue {
            $name = mb_trim($venueData->name);

            $this->nameLock->lock(GuardedName::VenueName, null, $name);

            if (Venue::query()->withTrashed()->whereName($name)->exists()) {
                throw NameTakenException::name($name);
            }

            return Venue::query()->create([
                'name' => $name,
                'address' => $venueData->address,
                'timezone' => $venueData->timezone,
            ]);
        });
    }
}

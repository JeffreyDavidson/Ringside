<?php

declare(strict_types=1);

namespace App\Actions\Venues;

use App\Data\Events\VenueData;
use App\Enums\Naming\GuardedName;
use App\Exceptions\Venues\NameTakenException;
use App\Lifecycle\Naming\RecordNameLock;
use App\Models\Events\Venue;
use Illuminate\Support\Facades\DB;

class UpdateAction
{
    public function __construct(private readonly RecordNameLock $nameLock) {}

    /**
     * Update a venue.
     *
     * This handles the complete venue update workflow:
     * - Rejects a name another venue already uses (deleted ones count, as they do for the form rule); venue names are
     *   unique across all promotions, and nothing in the database keeps them unique, so it first takes the name lock,
     *   before the venue's own row lock
     * - Updates venue location and facility information
     * - Maintains data integrity for existing event bookings
     * - Preserves venue history and event associations
     *
     * @param  Venue  $venue  The venue to update
     * @param  VenueData  $venueData  The updated venue information
     * @return Venue The updated venue instance
     *
     * @throws NameTakenException When another venue already has the name
     */
    public function handle(Venue $venue, VenueData $venueData): Venue
    {
        return DB::transaction(function () use ($venue, $venueData): Venue {
            $name = mb_trim($venueData->name);

            $this->nameLock->lock(GuardedName::VenueName, null, $name);

            $lockedVenue = $venue->refreshForUpdate();

            $nameTaken = Venue::query()
                ->withTrashed()
                ->whereName($name)
                ->whereKeyNot($lockedVenue->getKey())
                ->exists();

            if ($nameTaken) {
                throw NameTakenException::name($name);
            }

            $lockedVenue->update([
                'name' => $name,
                'address' => $venueData->address,
                'timezone' => $venueData->timezone,
            ]);

            return $lockedVenue;
        });
    }
}

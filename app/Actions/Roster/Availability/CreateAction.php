<?php

declare(strict_types=1);

namespace App\Actions\Roster\Availability;

use App\Exceptions\Roster\Availability\UnavailabilityException;
use App\Models\Roster\Availability\Unavailability;
use App\Models\Roster\Referees\Referee;
use App\Models\Roster\Wrestlers\Wrestler;
use App\Models\Users\User;
use Illuminate\Support\Facades\DB;

class CreateAction
{
    public function handle(Wrestler|Referee $person, string $startsOn, string $endsOn, ?string $reason, User $actor): Unavailability
    {
        return DB::transaction(function () use ($person, $startsOn, $endsOn, $reason, $actor): Unavailability {
            $person = $person->newQuery()->whereKey($person->id)->lockForUpdate()->firstOrFail();
            if ($person->promotion_id === null) {
                throw UnavailabilityException::unowned();
            }
            $reason = $reason === null || mb_trim($reason) === '' ? null : mb_trim($reason);
            if (Unavailability::query()->whereMorphedTo('person', $person)->where('promotion_id', $person->promotion_id)
                ->active()->where('starts_on', $startsOn)->where('ends_on', $endsOn)->where('reason', $reason)->exists()) {
                throw UnavailabilityException::duplicate();
            }
            $entry = new Unavailability;
            $entry->fill(['starts_on' => $startsOn, 'ends_on' => $endsOn, 'reason' => $reason]);
            $entry->promotion_id = $person->promotion_id;
            $entry->person_type = $person->getMorphClass();
            $entry->person_id = $person->id;
            $entry->created_by = $actor->id;
            $entry->save();
            activity('availability')->performedOn($person)->causedBy($actor)
                ->withProperties(['entry_id' => $entry->id, 'starts_on' => $startsOn, 'ends_on' => $endsOn])->log('create');

            return $entry;
        }, attempts: 3);
    }
}

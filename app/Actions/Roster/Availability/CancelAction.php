<?php

declare(strict_types=1);

namespace App\Actions\Roster\Availability;

use App\Models\Roster\Availability\Unavailability;
use App\Models\Roster\Referees\Referee;
use App\Models\Roster\Wrestlers\Wrestler;
use App\Models\Users\User;
use Illuminate\Support\Facades\DB;

class CancelAction
{
    public function handle(Wrestler|Referee $person, int $entryId, User $actor): void
    {
        DB::transaction(function () use ($person, $entryId, $actor): void {
            $person = $person->newQuery()
                ->whereKey($person->id)
                ->lockForUpdate()
                ->firstOrFail();

            $entry = Unavailability::query()
                ->whereMorphedTo('person', $person)
                ->where('promotion_id', $person->promotion_id)
                ->whereKey($entryId)
                ->lockForUpdate()
                ->firstOrFail();

            if ($entry->cancelled_at !== null) {
                return;
            }

            $entry->forceFill(['cancelled_at' => now(), 'cancelled_by' => $actor->id])->save();

            activity('availability')
                ->performedOn($person)
                ->causedBy($actor)
                ->withProperties(['entry_id' => $entry->id])
                ->log('cancel');
        }, attempts: 3);
    }
}

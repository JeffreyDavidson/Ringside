<?php

declare(strict_types=1);

namespace App\Rules\Referees;

use App\Lifecycle\Roster\RosterBookingEligibility;
use App\Lifecycle\Roster\RosterDateAvailability;
use App\Models\Events\Event;
use App\Models\Matches\EventMatch;
use App\Models\Roster\Referees\Referee;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class IsBookable implements ValidationRule
{
    public function __construct(private readonly ?Event $event = null, private readonly ?EventMatch $match = null) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $referee = Referee::query()->find($value);

        if (! $referee instanceof Referee || ! resolve(RosterBookingEligibility::class)->allows($referee, $this->event?->date)) {
            $fail(__('referees.validation.not_bookable'));

            return;
        }
        if ($this->event instanceof Event) {
            $dates = resolve(RosterDateAvailability::class);
            if (! $dates->retained($referee, $this->event, $this->match) && ! $dates->allowsForEvent($referee, $this->event)) {
                $fail(__('availability.unavailable', ['name' => $referee->full_name, 'date' => $this->event->local_date?->toDateString()]));
            }
        }
    }
}

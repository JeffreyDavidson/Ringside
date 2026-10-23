<?php

declare(strict_types=1);

namespace App\Rules\Wrestlers;

use App\Lifecycle\Roster\RosterBookingEligibility;
use App\Lifecycle\Roster\RosterDateAvailability;
use App\Models\Events\Event;
use App\Models\Matches\EventMatch;
use App\Models\Roster\Wrestlers\Wrestler;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class IsBookable implements ValidationRule
{
    public function __construct(private readonly ?Event $event = null, private readonly ?EventMatch $match = null) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        /** @var Wrestler|null $wrestler */
        $wrestler = Wrestler::find($value);

        if (! $wrestler || ! resolve(RosterBookingEligibility::class)->allows($wrestler, $this->event?->date)) {
            $fail(__('wrestlers.validation.not_bookable'));

            return;
        }

        if ($this->event instanceof Event) {
            $dates = resolve(RosterDateAvailability::class);

            if (! $dates->retained($wrestler, $this->event, $this->match) && ! $dates->allowsForEvent($wrestler, $this->event)) {
                $fail(__('availability.unavailable', ['name' => $wrestler->name, 'date' => $this->event->local_date?->toDateString()]));
            }
        }
    }
}

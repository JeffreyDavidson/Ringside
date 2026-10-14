<?php

declare(strict_types=1);

namespace App\Rules\TagTeams;

use App\Lifecycle\Roster\RosterBookingEligibility;
use App\Lifecycle\Roster\RosterDateAvailability;
use App\Models\Events\Event;
use App\Models\Matches\EventMatch;
use App\Models\Roster\TagTeams\TagTeam;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class IsBookable implements ValidationRule
{
    public function __construct(private readonly ?Event $event = null, private readonly ?EventMatch $match = null) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $tagTeam = TagTeam::find($value);

        if (! $tagTeam instanceof TagTeam || ! resolve(RosterBookingEligibility::class)->allows($tagTeam)) {
            $fail(__('tag-teams.validation.not_bookable'));

            return;
        }
        if ($this->event instanceof Event) {
            $dates = resolve(RosterDateAvailability::class);
            if (! $dates->retained($tagTeam, $this->event, $this->match) && ! $dates->allowsForEvent($tagTeam, $this->event)) {
                $fail(__('availability.unavailable', ['name' => $tagTeam->name, 'date' => $this->event->local_date?->toDateString()]));
            }
        }
    }
}

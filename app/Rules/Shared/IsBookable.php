<?php

declare(strict_types=1);

namespace App\Rules\Shared;

use App\Lifecycle\Roster\RosterBookingEligibility;
use App\Lifecycle\Roster\RosterDateAvailability;
use App\Models\Events\Event;
use App\Models\Matches\EventMatch;
use App\Models\Roster\Referees\Referee;
use App\Models\Roster\TagTeams\TagTeam;
use App\Models\Roster\Wrestlers\Wrestler;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/** The wrestler, referee or tag team picked for a match must be bookable, and free on the booked event's date. */
class IsBookable implements ValidationRule
{
    /** @param  class-string<Wrestler|Referee|TagTeam>  $model */
    public function __construct(
        private readonly string $model,
        private readonly ?Event $event = null,
        private readonly ?EventMatch $match = null,
    ) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_int($value) && ! is_string($value)) {
            $fail($this->notBookableMessage());

            return;
        }

        $person = $this->model::query()->find($value);

        if (! $person instanceof $this->model || ! resolve(RosterBookingEligibility::class)->allows($person, $this->event?->date)) {
            $fail($this->notBookableMessage());

            return;
        }

        if ($this->event instanceof Event) {
            $dates = resolve(RosterDateAvailability::class);

            if (! $dates->retained($person, $this->event, $this->match) && ! $dates->allowsForEvent($person, $this->event)) {
                $fail(__('availability.unavailable', [
                    'name' => $person instanceof Referee ? $person->full_name : $person->name,
                    'date' => $this->event->local_date?->toDateString(),
                ]));
            }
        }
    }

    private function notBookableMessage(): string
    {
        return match ($this->model) {
            Wrestler::class => __('wrestlers.validation.not_bookable'),
            Referee::class => __('referees.validation.not_bookable'),
            default => __('tag-teams.validation.not_bookable'),
        };
    }
}

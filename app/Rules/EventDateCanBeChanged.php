<?php

declare(strict_types=1);

namespace App\Rules;

use App\Models\Event;
use Illuminate\Contracts\Validation\Rule;
use Illuminate\Support\Carbon;

class EventDateCanBeChanged implements Rule
{
    /**
     * The event to be checked against.
     *
     * @var \App\Models\Event
     */
    protected $event;

    /**
     * Create a new event date can be changed rule instance.
     */
    public function __construct(Event $event)
    {
        $this->event = $event;
    }

    /**
     * Determine if the validation rule passes.
     *
     *
     * @phpcsSuppress SlevomatCodingStandard.Functions.UnusedParameter
     */
    public function passes(string $attribute, Carbon $value): bool
    {
        if ($this->event->date === null) {
            return true;
        }

        if ($this->event->date->isFuture() && Carbon::parse($value)->gt(now())) {
            return true;
        }

        return false;
    }

    /**
     * Get the validation error message.
     */
    public function message(): string
    {
        return 'The validation error message.';
    }
}

<?php

namespace App\Rules;

use App\Models\Event;
use Carbon\Carbon;
use Illuminate\Contracts\Validation\Rule;

class EventDateCanBeChanged implements Rule
{
    protected $event;

    public function __construct(Event $event)
    {
        $this->event = $event;
    }

    /**
     * Determine if the validation rule passes.
     *
     * @param  string  $attribute
     * @param  mixed  $value
     * @return bool
     */
    public function passes($attribute, $value)
    {
        if (is_null($this->event->date)) {
            return true;
        }

        if ($this->event->date->isFuture() && Carbon::parse($value)->gt(now())) {
            return true;
        }

        return false;
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message()
    {
        return 'The validation error message.';
    }
}

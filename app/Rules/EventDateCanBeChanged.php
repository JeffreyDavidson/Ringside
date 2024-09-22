<?php

declare(strict_types=1);

namespace App\Rules;

use App\Models\Event;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class EventDateCanBeChanged implements ValidationRule
{
    public function __construct(protected Event $event) {}

    /**
     * Determine if the validation rule passes.
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if ($this->event->date === null) {
            $fail('The validation error message.');
        }

        if ($this->event->date?->isPast()) {
            $fail('The validation error message.');
        }
    }
}

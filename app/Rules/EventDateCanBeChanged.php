<?php

declare(strict_types=1);

namespace App\Rules;

use App\Models\Event;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Carbon;

class EventDateCanBeChanged implements ValidationRule
{
    public function __construct(protected Event $event)
    {
        $this->event = $event;
    }

    /**
     * Determine if the validation rule passes.
     *
     * @phpcsSuppress SlevomatCodingStandard.Functions.UnusedParameter
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if ($this->event->date === null) {
            $fail('The validation error message.');
        }

        if ($this->event->date?->isFuture() && Carbon::parse($value)->gt(now())) {
            $fail('The validation error message.');
        }
    }
}

<?php

declare(strict_types=1);

namespace App\Rules\Events;

use App\Models\Events\Event;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class DateCanBeChanged implements ValidationRule
{
    public function __construct(private ?Event $event) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if ($this->event && $this->event->hasPastDate()) {
            $fail('Cannot change the date of an event that has already occurred.');
        }
    }
}

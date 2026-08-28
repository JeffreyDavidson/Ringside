<?php

declare(strict_types=1);

namespace App\Rules\Events;

use App\Exceptions\Events\CannotBeRescheduledException;
use App\Lifecycle\Events\EventSchedulingEligibility;
use App\Models\Events\Event;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Facades\Date;

class DateCanBeChanged implements ValidationRule
{
    public function __construct(private ?Event $event) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! $this->event) {
            return;
        }

        if ($value !== null && ! is_string($value)) {
            return;
        }

        try {
            EventSchedulingEligibility::ensureDateCanChange(
                $this->event,
                $value === null ? null : Date::parse($value),
            );
        } catch (CannotBeRescheduledException $exception) {
            $fail($exception->getMessage());
        }
    }
}

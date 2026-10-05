<?php

declare(strict_types=1);

namespace App\Rules\Events;

use App\Models\Promotions\Promotion;
use Carbon\Exceptions\InvalidFormatException;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/** Rejects a wall-clock time that the promotion's zone skips when clocks move forward, instead of silently shifting it. */
class LocalTimeExists implements ValidationRule
{
    public function __construct(private readonly ?Promotion $promotion) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value)) {
            return;
        }

        try {
            $exists = Promotion::localTimeExists($this->promotion, $value);
        } catch (InvalidFormatException) {
            return;
        }

        if (! $exists) {
            $fail(__('events.date_does_not_exist', ['timezone' => Promotion::zoneOf($this->promotion)]));
        }
    }
}

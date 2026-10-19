<?php

declare(strict_types=1);

namespace App\Rules\Titles;

use App\Models\Events\Event;
use App\Models\Titles\Title;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class IsActive implements ValidationRule
{
    /** The title must have been active on the booked event's date; without a dated event it must be active now. */
    public function __construct(private readonly ?Event $event = null) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $isActive = Title::query()
            ->whereKey($value)
            ->activeOn($this->event?->date?->copy()->utc())
            ->exists();

        if (! $isActive) {
            $fail(__('titles.validation.inactive'));
        }
    }
}

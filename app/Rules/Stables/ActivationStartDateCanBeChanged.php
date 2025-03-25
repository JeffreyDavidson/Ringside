<?php

declare(strict_types=1);

namespace App\Rules\Stables;

use App\Models\Stable;
use Closure;
use DateTimeInterface;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Carbon;

class ActivationStartDateCanBeChanged implements ValidationRule
{
    public function __construct(private Stable $stable) {}

    /**
     * Determine if the validation rule passes.
     *
     * @param  DateTimeInterface|string|null  $value
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if ($this->stable->isCurrentlyActivated() && ! $this->stable->activatedOn(Carbon::parse($value))) {
            $fail("{$this->stable->name} is currently activated and the activation date cannot be changed.");
        }
    }
}

<?php

namespace App\Rules;

use App\Models\Title;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Carbon;

class ActivationStartDateCanBeChanged implements ValidationRule
{
    public function __construct(protected Title $title)
    {
    }

    /**
     * Determine if the validation rule passes.
     *
     * @phpcsSuppress SlevomatCodingStandard.Functions.UnusedParameter
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if ($this->title->isCurrentlyActivated() && ! $this->title->activatedOn(Carbon::parse($value))) {
            $fail("{$this->title->name} is currently activated and the activation date cannot be changed.");
        }
    }
}

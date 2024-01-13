<?php

declare(strict_types=1);

namespace App\Rules;

use App\Models\Contracts\Employable;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Carbon;

class EmploymentStartDateCanBeChanged implements ValidationRule
{
    public function __construct(protected Employable $rosterMember)
    {
    }

    /**
     * Determine if the validation rule passes.
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $name = '';

        if ($this->rosterMember->isReleased() && ! $this->rosterMember->employedOn(Carbon::parse($value))) {
            $fail("{$name} was released and the start date cannot be changed.");
        }

        if ($this->rosterMember->isCurrentlyEmployed() && ! $this->rosterMember->employedOn(Carbon::parse($value))) {
            $fail("{$name} is currently employed and the start date cannot be changed.");
        }
    }
}

<?php

namespace App\Rules\Stables;

use App\Models\Stable;
use Illuminate\Contracts\Validation\Rule;
use Illuminate\Support\Carbon;

class ActivationStartDateCanBeChanged implements Rule
{
    /**
     * Undocumented variable.
     *
     * @var \App\Models\Stable
     */
    private $stable;

    /**
     * Create a new rule instance.
     *
     * @return void
     */
    public function __construct(Stable $stable)
    {
        $this->stable = $stable;
    }

    /**
     * Determine if the validation rule passes.
     *
     *
     * @phpcsSuppress SlevomatCodingStandard.Functions.UnusedParameter
     */
    public function passes(string $attribute, Carbon $value): bool
    {
        if ($this->stable->isCurrentlyActivated() && ! $this->stable->activatedOn(Carbon::parse($value))) {
            return false;
        }

        return true;
    }

    /**
     * Get the validation error message.
     */
    public function message(): string
    {
        return "{$this->stable->name} is currently activated and the activation date cannot be changed.";
    }
}

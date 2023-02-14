<?php

namespace App\Rules;

use App\Models\Title;
use Illuminate\Contracts\Validation\Rule;
use Illuminate\Support\Carbon;

class ActivationStartDateCanBeChanged implements Rule
{
    /**
     * Undocumented variable.
     *
     * @var \App\Models\Title
     */
    private $title;

    /**
     * Create a new rule instance.
     *
     * @return void
     */
    public function __construct(Title $title)
    {
        $this->title = $title;
    }

    /**
     * Determine if the validation rule passes.
     *
     *
     * @phpcsSuppress SlevomatCodingStandard.Functions.UnusedParameter
     */
    public function passes(string $attribute, string $value): bool
    {
        if ($this->title->isCurrentlyActivated() && ! $this->title->activatedOn(Carbon::parse($value))) {
            return false;
        }

        return true;
    }

    /**
     * Get the validation error message.
     */
    public function message(): string
    {
        return "{$this->title->name} is currently activated and the activation date cannot be changed.";
    }
}

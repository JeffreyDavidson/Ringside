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
     * @param  \App\Models\Title  $title
     * @return void
     */
    public function __construct(Title $title)
    {
        $this->title = $title;
    }

    /**
     * Determine if the validation rule passes.
     *
     * @param  string  $attribute
     * @param  string  $value
     * @return bool
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
     *
     * @return string
     */
    public function message(): string
    {
        return "{$this->title->name} is currently activated and the activation date cannot be changed.";
    }
}

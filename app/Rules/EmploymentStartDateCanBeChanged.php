<?php

namespace App\Rules;

use App\Models\Contracts\Employable;
use Illuminate\Contracts\Validation\Rule;
use Illuminate\Support\Carbon;

class EmploymentStartDateCanBeChanged implements Rule
{
    /**
     * Undocumented variable.
     *
     * @var \App\Models\Contracts\Employable
     */
    protected Employable $rosterMember;

    /**
     * Undocumented variable.
     *
     * @var string
     */
    private $message;

    /**
     * Create a new rule instance.
     *
     * @return void
     */
    public function __construct(Employable $rosterMember)
    {
        $this->rosterMember = $rosterMember;
    }

    /**
     * Determine if the validation rule passes.
     *
     *
     * @phpcsSuppress SlevomatCodingStandard.Functions.UnusedParameter
     */
    public function passes(string $attribute, string $value): bool
    {
        $name = $this->rosterMember->name;

        if ($this->rosterMember->isReleased() && ! $this->rosterMember->employedOn(Carbon::parse($value))) {
            $this->message = "{$name} was released and the start date cannot be changed.";

            return false;
        }

        if ($this->rosterMember->isCurrentlyEmployed() && ! $this->rosterMember->employedOn(Carbon::parse($value))) {
            $this->message = "{$name} is currently employed and the start date cannot be changed.";

            return false;
        }

        return true;
    }

    /**
     * Get the validation error message.
     */
    public function message(): string
    {
        return $this->message;
    }
}

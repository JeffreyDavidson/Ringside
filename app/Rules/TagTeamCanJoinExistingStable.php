<?php

namespace App\Rules;

use App\Models\TagTeam;
use Illuminate\Contracts\Validation\Rule;
use Illuminate\Support\Carbon;

class TagTeamCanJoinExistingStable implements Rule
{
    /**
     * @var string
     */
    protected $messages;

    /**
     * Undocumented variable.
     *
     * @var \Illuminate\Support\Carbon|null
     */
    protected $startDate;

    /**
     * Undocumented function.
     *
     * @param  \Illuminate\Support\Carbon|null  $startDate
     */
    public function __construct(?Carbon $startDate)
    {
        $this->startDate = $startDate;
    }

    /**
     * Determine if the validation rule passes.
     *
     * @param  string  $attribute
     * @param  mixed  $value
     * @return bool
     *
     * @phpcsSuppress SlevomatCodingStandard.Functions.UnusedParameter
     */
    public function passes($attribute, $value)
    {
        /** @var \App\Models\TagTeam $tagTeam */
        $tagTeam = TagTeam::with(['currentWrestlers', 'currentStable'])->whereKey($value)->sole();

        if ($tagTeam->currentStable !== null) {
            return false;
        }

        if ($tagTeam->isSuspended()) {
            $this->messages = "{$tagTeam->name} is supsended and cannot join the stable.";

            return false;
        }

        if ($tagTeam->isCurrentlyEmployed()
            && isset($this->startDate)
            && ! $tagTeam->employedBefore($this->startDate)
        ) {
            $this->messages = "{$tagTeam->name} cannot have an employment start date after stable's start date.";

            return false;
        }

        return true;
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message()
    {
        return $this->messages;
    }
}

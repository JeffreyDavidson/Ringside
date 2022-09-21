<?php

namespace App\Rules;

use App\Models\Wrestler;
use Illuminate\Contracts\Validation\Rule;
use Illuminate\Support\Carbon;

class WrestlerCanJoinExistingStable implements Rule
{
    /**
     * @var array
     */
    protected $tagTeamIds;

    /**
     * @var string
     */
    protected $date;

    /**
     * Undocumented variable.
     *
     * @var string
     */
    protected string $messages;

    /**
     * Create a new rule instance.
     *
     * @param  array  $tagTeamIds
     * @param  string  $date
     * @return void
     */
    public function __construct($tagTeamIds, $date)
    {
        $this->tagTeamIds = $tagTeamIds;
        $this->date = $date;
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
        if (! is_array($this->tagTeamIds)) {
            return false;
        }

        /** @var \App\Models\Wrestler $wrestler */
        $wrestler = Wrestler::with('currentStable')->whereKey($value)->first();

        if ($wrestler->isSuspended()) {
            $this->messages = "{$wrestler->name} is suspended and cannot join stable.";

            return false;
        }

        if ($wrestler->isInjured()) {
            $this->messages = "{$wrestler->name} is injured and cannot join stable.";

            return false;
        }

        if ($wrestler->isCurrentlyEmployed() && ! $wrestler->employedBefore(Carbon::parse($this->date))) {
            $this->messages = "{$wrestler->name} cannot have an employment start date after stable's start date.";

            return false;
        }

        if ($this->tagTeamIds !== 0) {
            collect($this->tagTeamIds)->map(function ($id) use ($wrestler) {
                if ($id === $wrestler->currentTagTeam?->id) {
                    return false;
                }
            });
        }

        if ($wrestler->currentStable !== null && $wrestler->currentStable->exists()) {
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

<?php

namespace App\Rules;

use App\Models\Wrestler;
use Illuminate\Contracts\Validation\Rule;

class WrestlerCanJoinExistingStable implements Rule
{
    /**
     * @var array
     */
    protected $tagTeamIds;

    /**
     * Create a new rule instance.
     *
     * @return void
     */
    public function __construct(array $tagTeamIds)
    {
        $this->tagTeamIds = $tagTeamIds;
    }

    /**
     * Determine if the validation rule passes.
     *
     * @param  string  $attribute
     * @param  mixed  $value
     * @return bool
     */
    public function passes($attribute, $value)
    {
        $wrestler = Wrestler::with(['currentStable', 'currentTagTeam'])->whereKey($value)->sole();

        if ($wrestler->isSuspended()) {
            $this->messages = "{$wrestler->name} is suspended and cannot join stable.";

            return false;
        }

        if ($wrestler->isInjured()) {
            $this->messages = "{$wrestler->name} is injured and cannot join stable.";

            return false;
        }

        if ($wrestler->isCurrentlyEmployed() && ! $wrestler->employedBefore($this->date('started_at'))) {
            $this->messages = "{$wrestler->name} cannot have an employment start date after stable's start date.";

            return false;
        }

        if ($this->tagTeamIds !== 0) {
            collect($this->tagTeamIds)->map(function ($id) use ($wrestler) {
                if ($id === $wrestler->currentTagTeam->id) {
                    return false;
                }
            });
        }

        if ($wrestler->currentStable !== null && $wrestler->currentStable->exists()) {
            return false;
        }
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

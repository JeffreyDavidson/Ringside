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
        return 'This wrestler is already a member of a stable.';
    }
}

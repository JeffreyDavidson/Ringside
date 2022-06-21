<?php

namespace App\Rules\Stables;

use Illuminate\Contracts\Validation\Rule;

class HasMinimumAmountOfMembers implements Rule
{
    /**
     * Determine if the validation rule passes.
     *
     * @param  string  $attribute
     * @param  mixed  $value
     * @return bool
     */
    public function passes($attribute, $value)
    {
        if ($stable->isCurrentlyActivated() || $this->date('activated_at')) {
            $tagTeamsCountFromRequest = $this->collect('tag_teams')->count();
            $wrestlersCountFromRequest = $this->collect('wrestlers')->count();

            $tagTeamMembersCount = $tagTeamsCountFromRequest * 2;

            if ($tagTeamMembersCount + $wrestlersCountFromRequest < 3) {
                $validator->errors()->add(
                    '*',
                    "{$stable->name} does not contain at least 3 members."
                );

                $validator->addFailure('*', 'not_enough_members');
            }
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
        return "{$this->stable->name} is currently activated and the activation date cannot be changed.";
    }
}

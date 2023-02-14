<?php

namespace App\Rules;

use App\Models\Wrestler;
use Illuminate\Contracts\Validation\Rule;

class WrestlerCanJoinNewStable implements Rule
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
     * @param  mixed  $value
     *
     * @phpcsSuppress SlevomatCodingStandard.Functions.UnusedParameter
     */
    public function passes(string $attribute, $value): bool
    {
        /** @var \App\Models\Wrestler $wrestler */
        $wrestler = Wrestler::with(['currentStable'])->find($value);

        if (! is_null($wrestler->currentStable) && $wrestler->currentStable->exists()) {
            return false;
        }

        if (is_array($this->tagTeamIds) && count($this->tagTeamIds) > 0) {
            if (! is_null($wrestler->currentTagTeam)
                && collect($this->tagTeamIds)->contains($wrestler->currentTagTeam->id)
            ) {
                return false;
            }
        }

        return true;
    }

    /**
     * Get the validation error message.
     */
    public function message(): string
    {
        return 'This wrestler is already a member of a stable.';
    }
}

<?php

namespace App\Rules;

use App\Models\Wrestler;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class WrestlerCanJoinNewStable implements ValidationRule
{
    public function __construct(protected array $tagTeamIds)
    {
        $this->tagTeamIds = $tagTeamIds;
    }

    /**
     * Determine if the validation rule passes.
     *
     * @phpcsSuppress SlevomatCodingStandard.Functions.UnusedParameter
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        /** @var \App\Models\Wrestler $wrestler */
        $wrestler = Wrestler::with(['currentStable'])->find($value);

        if (! is_null($wrestler->currentStable) && $wrestler->currentStable->exists()) {
            $fail('This wrestler is already a member of a stable.');
        }

        if (is_array($this->tagTeamIds) && count($this->tagTeamIds) > 0) {
            if (! is_null($wrestler->currentTagTeam)
                && collect($this->tagTeamIds)->contains($wrestler->currentTagTeam->id)
            ) {
                $fail('This wrestler is already a member of a stable.');
            }
        }
    }
}

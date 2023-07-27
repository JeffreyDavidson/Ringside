<?php

declare(strict_types=1);

namespace App\Rules;

use App\Models\Wrestler;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Collection;

class WrestlerCanJoinNewStable implements ValidationRule
{
    public function __construct(protected Collection $tagTeamIds)
    {
    }

    /**
     * Determine if the validation rule passes.
     *
     * @phpcsSuppress SlevomatCodingStandard.Functions.UnusedParameter
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        /** @var Wrestler $wrestler */
        $wrestler = Wrestler::with(['currentStable'])->find($value);

        if (! is_null($wrestler->currentStable) && $wrestler->currentStable->exists()) {
            $fail('This wrestler is already a member of a stable.');
        }

        if ($this->tagTeamIds->isNotEmpty()) {
            if (! is_null($wrestler->currentTagTeam)
                && $this->tagTeamIds->contains($wrestler->currentTagTeam->id)
            ) {
                $fail('This wrestler is already a member of a stable.');
            }
        }
    }
}

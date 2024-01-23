<?php

declare(strict_types=1);

namespace App\Rules;

use App\Models\TagTeam;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Carbon;

class TagTeamCanJoinExistingStable implements ValidationRule
{
    public function __construct(protected ?Carbon $startDate)
    {
        $this->startDate = $startDate;
    }

    /**
     * Determine if the validation rule passes.
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        /** @var \App\Models\TagTeam $tagTeam */
        $tagTeam = TagTeam::with(['currentWrestlers', 'currentStable'])->whereKey($value)->sole();

        if ($tagTeam->currentStable !== null) {
            $fail("{$tagTeam->name} are already members of an existing stable.");
        }

        if ($tagTeam->isSuspended()) {
            $fail("{$tagTeam->name} is supsended and cannot join the stable.");
        }

        if ($tagTeam->isCurrentlyEmployed()
            && isset($this->startDate)
            && ! $tagTeam->employedBefore($this->startDate)
        ) {
            $fail("{$tagTeam->name} cannot have an employment start date after stable's start date.");
        }
    }
}

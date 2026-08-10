<?php

declare(strict_types=1);

namespace App\Rules\TagTeams;

use App\Models\TagTeams\TagTeam;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Carbon;

class CanJoinStable implements ValidationRule
{
    public function __construct(
        private ?int $stableId = null,
        private ?Carbon $stableStartDate = null
    ) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $tagTeam = TagTeam::query()->findOrFail($value);

        if ($this->stableId !== null && $tagTeam->stables()
            ->whereKey($this->stableId)
            ->wherePivotNull('left_at')
            ->exists()) {
            return;
        }

        $currentStable = $tagTeam->currentStable()->first();

        if ($currentStable) {
            $fail("{$tagTeam->name} already belongs to {$currentStable->name} and cannot join another stable.");

            return;
        }

        // Check if suspended
        if ($tagTeam->isSuspended()) {
            $fail("{$tagTeam->name} is suspended and cannot join the stable.");

            return;
        }

        if (! $tagTeam->isEmployed()) {
            $fail("{$tagTeam->name} is not employed and cannot join the stable.");

            return;
        }

        if ($this->stableStartDate &&
            ! $tagTeam->employmentStartedBefore($this->stableStartDate)) {
            $fail("{$tagTeam->name} cannot have an employment start date after stable's start date.");
        }
    }
}

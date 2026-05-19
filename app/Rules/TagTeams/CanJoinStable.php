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
        private bool $isNewStable = false,
        private ?Carbon $stableStartDate = null
    ) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $tagTeam = $this->isNewStable
            ? TagTeam::with(['currentWrestlers', 'currentStable'])->whereKey($value)->sole()
            : TagTeam::findOrFail($value);

        // Check if suspended
        if ($tagTeam->isSuspended()) {
            $fail("{$tagTeam->name} is suspended and cannot join the stable.");

            return;
        }

        // Check if already in a stable
        $currentStable = $this->isNewStable ? $tagTeam->currentStable : $tagTeam->currentStable()->first();
        if ($currentStable !== null) {
            $message = $this->isNewStable
                ? 'This tag team is already a member of a stable.'
                : "{$tagTeam->name} are already members of an existing stable.";
            $fail($message);

            return;
        }

        // Employment validation for existing stables
        if (! $this->isNewStable &&
            $this->stableStartDate &&
            $tagTeam->isEmployed() &&
            method_exists($tagTeam, 'employedBefore') &&
            ! $tagTeam->employedBefore($this->stableStartDate)) {
            $fail("{$tagTeam->name} cannot have an employment start date after stable's start date.");
        }
    }
}

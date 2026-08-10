<?php

declare(strict_types=1);

namespace App\Rules\Wrestlers;

use App\Models\Wrestlers\Wrestler;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class CanJoinStable implements ValidationRule
{
    /**
     * @param  Collection<int, int>  $tagTeamIds
     */
    public function __construct(
        private ?int $stableId = null,
        private ?Carbon $stableStartDate = null,
        private Collection $tagTeamIds = new Collection()
    ) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        /** @var Wrestler $wrestler */
        $wrestler = Wrestler::findOrFail($value);

        if ($this->stableId !== null && $wrestler->stables()
            ->whereKey($this->stableId)
            ->wherePivotNull('left_at')
            ->exists()) {
            return;
        }

        $currentStable = $wrestler->currentStable()->first();

        if ($currentStable) {
            $fail("{$wrestler->name} already belongs to {$currentStable->name} and cannot join another stable.");

            return;
        }

        // Common validations for both new and existing stables
        if ($wrestler->isSuspended()) {
            $fail("{$wrestler->name} is suspended and cannot join the stable.");

            return;
        }

        if ($wrestler->isInjured()) {
            $fail("{$wrestler->name} is injured and cannot join the stable.");

            return;
        }

        if (! $wrestler->isEmployed()) {
            $fail("{$wrestler->name} is not employed and cannot join the stable.");

            return;
        }

        if ($this->stableStartDate &&
            ! $wrestler->employmentStartedBefore($this->stableStartDate)) {
            $fail("{$wrestler->name} cannot have an employment start date after stable's start date.");

            return;
        }

        // Tag team conflict check
        if ($this->tagTeamIds->isNotEmpty()) {
            $currentTagTeam = $wrestler->currentTagTeam()->first();
            if ($currentTagTeam && $this->tagTeamIds->contains($currentTagTeam->getKey())) {
                $fail('This wrestler is already represented in the stable through their tag team.');
            }
        }
    }
}

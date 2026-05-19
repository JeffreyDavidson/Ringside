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
        private bool $isNewStable = false,
        private ?Carbon $stableStartDate = null,
        private Collection $tagTeamIds = new Collection()
    ) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        /** @var Wrestler $wrestler */
        $wrestler = Wrestler::findOrFail($value);

        // Common validations for both new and existing stables
        if ($wrestler->isSuspended()) {
            $fail("{$wrestler->name} is suspended and cannot join the stable.");

            return;
        }

        if ($wrestler->isInjured()) {
            $fail("{$wrestler->name} is injured and cannot join the stable.");

            return;
        }

        // Check if already in a stable
        if ($wrestler->currentStable()->first() !== null) {
            $message = $this->isNewStable
                ? 'This wrestler is already a member of a stable.'
                : 'This wrestler already belongs to a current stable.';
            $fail($message);

            return;
        }

        // Employment date validation for existing stables
        if (! $this->isNewStable &&
            $this->stableStartDate &&
            $wrestler->isEmployed() &&
            method_exists($wrestler, 'employedBefore') &&
            ! $wrestler->employedBefore($this->stableStartDate)) {
            $fail("{$wrestler->name} cannot have an employment start date after stable's start date.");

            return;
        }

        // Tag team conflict check
        if ($this->tagTeamIds->isNotEmpty()) {
            $currentTagTeam = $wrestler->currentTagTeam()->first();
            if ($currentTagTeam && $this->tagTeamIds->contains($currentTagTeam->getKey())) {
                $message = $this->isNewStable
                    ? 'This wrestler is already a member of a stable through their tag team.'
                    : 'A wrestler in a tag team already belongs to a current stable.';
                $fail($message);
            }
        }
    }
}

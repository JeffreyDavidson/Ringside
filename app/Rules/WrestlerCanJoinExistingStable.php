<?php

namespace App\Rules;

use App\Models\Wrestler;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Carbon;

class WrestlerCanJoinExistingStable implements ValidationRule
{
    public function __construct(protected array $tagTeamIds, protected string $date)
    {
        $this->tagTeamIds = $tagTeamIds;
        $this->date = $date;
    }

    /**
     * Determine if the validation rule passes.
     *
     * @phpcsSuppress SlevomatCodingStandard.Functions.UnusedParameter
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        /** @var \App\Models\Wrestler $wrestler */
        $wrestler = Wrestler::with('currentStable')->whereKey($value)->first();

        if ($wrestler->isSuspended()) {
            $fail("{$wrestler->name} is suspended and cannot join stable.");
        }

        if ($wrestler->isInjured()) {
            $fail("{$wrestler->name} is injured and cannot join stable.");
        }

        if ($wrestler->isCurrentlyEmployed() && ! $wrestler->employedBefore(Carbon::parse($this->date))) {
            $fail("{$wrestler->name} cannot have an employment start date after stable's start date.");
        }

        if ($this->tagTeamIds !== 0) {
            collect($this->tagTeamIds)->map(function ($id) use ($wrestler, $fail) {
                if ($id === $wrestler->currentTagTeam?->id) {
                    $fail('A wrestler in a tag team already belongs to a current stable.');
                }
            });
        }

        if ($wrestler->currentStable !== null && $wrestler->currentStable->exists()) {
            $fail('This wrestler already belongs to a current stable.');
        }
    }
}

<?php

declare(strict_types=1);

namespace App\Rules;

use App\Models\Wrestler;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class WrestlerCanJoinExistingStable implements ValidationRule
{
    /**
     * @param  Collection<int, int>  $tagTeamIds
     */
    public function __construct(protected Collection $tagTeamIds, protected ?Carbon $date)
    {
    }

    /**
     * Determine if the validation rule passes.
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
            collect($this->tagTeamIds)->map(function (mixed $id) use ($wrestler, $fail) {
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

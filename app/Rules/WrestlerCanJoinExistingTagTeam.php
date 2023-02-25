<?php

namespace App\Rules;

use App\Models\TagTeam;
use App\Models\Wrestler;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class WrestlerCanJoinExistingTagTeam implements ValidationRule
{
    public function __construct(protected TagTeam $tagTeam)
    {
        $this->tagTeam = $tagTeam;
    }

    /**
     * Determine if the validation rule passes.
     *
     * @phpcsSuppress SlevomatCodingStandard.Functions.UnusedParameter
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        /** @var \App\Models\Wrestler $wrestler */
        $wrestler = Wrestler::query()->with(['currentEmployment', 'futureEmployment'])->whereKey($value)->sole();

        if (! $this->tagTeam->currentWrestlers->contains($wrestler)) {
            $fail('This wrestler cannot join the tag team.');
        }

        if ($wrestler->isSuspended() || $wrestler->isInjured()) {
            $fail('This wrestler cannot join the tag team.');
        }

        $bookableTagTeams = TagTeam::query()->bookable()->whereNotIn('id', [$this->tagTeam->id])->get();

        $bookableTagTeams->each(function ($tagTeam) use ($wrestler, $fail) {
            if ($tagTeam->currentWrestlers->contains($wrestler)) {
                $fail('This wrestler cannot join the tag team.');
            }
        });

        if (
            $wrestler->currentTagTeam !== null
              && $wrestler->currentTagTeam->exists()
              && $wrestler->currentTagTeam->is($this->tagTeam)
        ) {
            $fail('This wrestler cannot join the tag team.');
        }
    }
}

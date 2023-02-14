<?php

namespace App\Rules;

use App\Models\TagTeam;
use App\Models\Wrestler;
use Illuminate\Contracts\Validation\Rule;

class WrestlerCanJoinExistingTagTeam implements Rule
{
    /**
     * Undocumented variable.
     *
     * @var \App\Models\TagTeam
     */
    protected $tagTeam;

    /**
     * Undocumented function.
     */
    public function __construct(TagTeam $tagTeam)
    {
        $this->tagTeam = $tagTeam;
    }

    /**
     * Determine if the validation rule passes.
     *
     * @param  mixed  $value
     *
     * @phpcsSuppress SlevomatCodingStandard.Functions.UnusedParameter
     */
    public function passes(string $attribute, $value): bool
    {
        /** @var \App\Models\Wrestler $wrestler */
        $wrestler = Wrestler::query()->with(['currentEmployment', 'futureEmployment'])->whereKey($value)->sole();

        if ($this->tagTeam->currentWrestlers->contains($wrestler)) {
            return true;
        }

        if ($wrestler->isSuspended() || $wrestler->isInjured()) {
            return false;
        }

        $bookableTagTeams = TagTeam::query()->bookable()->whereNotIn('id', [$this->tagTeam->id])->get();

        $bookableTagTeams->each(function ($tagTeam) use ($wrestler) {
            if ($tagTeam->currentWrestlers->contains($wrestler)) {
                return false;
            }
        });

        if (
            $wrestler->currentTagTeam !== null
              && $wrestler->currentTagTeam->exists()
              && $wrestler->currentTagTeam->is($this->tagTeam)
        ) {
            return false;
        }

        return true;
    }

    /**
     * Get the validation error message.
     */
    public function message(): string
    {
        return 'This wrestler cannot join the tag team.';
    }
}

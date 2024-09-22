<?php

declare(strict_types=1);

namespace App\Rules;

use App\Models\TagTeam;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Carbon;

class TagTeamMustBeEmployedBeforeStableStartDate implements ValidationRule
{
    public function __construct(protected Carbon $stableStartDate) {}

    /**
     * Determine if the validation rule passes.
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        /** @var TagTeam $tagTeam */
        $tagTeam = TagTeam::with(['futureEmployment'])->whereKey($value)->sole();

        if (! $tagTeam->isCurrentlyEmployed()) {
            $fail('This tag team is not currently employed.');
        }

        if ($tagTeam->futureEmployment !== null) {
            $fail('This tag team has a future employment scheduled.');
        }

        if (! $tagTeam->futureEmployment?->startedBefore($this->stableStartDate)) {
            $fail("This tag team is not employed before the stable\'s activation date");
        }
    }
}

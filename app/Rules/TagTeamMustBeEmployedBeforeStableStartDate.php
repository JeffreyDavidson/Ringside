<?php

declare(strict_types=1);

namespace App\Rules;

use App\Models\TagTeam;
use Illuminate\Contracts\Validation\Rule;
use Illuminate\Support\Carbon;

class TagTeamMustBeEmployedBeforeStableStartDate implements Rule
{
    /**
     * The start date of the stable.
     *
     * @var \Illuminate\Support\Carbon
     */
    private $stableStartDate;

    /**
     * Create a new rule instance.
     *
     * @return void
     */
    public function __construct(Carbon $stableStartDate)
    {
        $this->stableStartDate = $stableStartDate;
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
        $tagTeam = TagTeam::with(['futureEmployment'])->whereKey($value)->sole();

        if ($tagTeam->isCurrentlyEmployed()) {
            return true;
        }

        if ($tagTeam->futureEmployment === null) {
            return true;
        }

        if ($tagTeam->futureEmployment->startedBefore($this->stableStartDate)) {
            return true;
        }

        return false;
    }

    /**
     * Get the validation error message.
     */
    public function message(): string
    {
        return 'This tag team is not employed before the stable\'s activation date';
    }
}

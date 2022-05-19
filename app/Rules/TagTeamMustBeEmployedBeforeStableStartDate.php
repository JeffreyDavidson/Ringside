<?php

declare(strict_types=1);

namespace App\Rules;

use App\Models\TagTeam;
use Illuminate\Support\Carbon;
use Illuminate\Contracts\Validation\Rule;

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
     * @param  \Illuminate\Support\Carbon $stableStartDate
     * @return void
     */
    public function __construct(Carbon $stableStartDate)
    {
        $this->stableStartDate = $stableStartDate;
    }

    /**
     * Determine if the validation rule passes.
     *
     * @param  string  $attribute
     * @param  mixed  $value
     * @return bool
     */
    public function passes($attribute, $value)
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
     *
     * @return string
     */
    public function message()
    {
        return 'This tag team is not employed before the stable\'s activation date';
    }
}

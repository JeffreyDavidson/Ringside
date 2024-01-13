<?php

declare(strict_types=1);

namespace App\Rules;

use App\Models\TagTeam;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class TagTeamMustBeBookable implements ValidationRule
{
    /**
     * Run the validation rule.
     *
     * @param  \Closure(string): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        /** @var TagTeam $tagTeam */
        $tagTeam = TagTeam::find($value);

        if (! $tagTeam->isBookable()) {
            $fail('This tag team is not bookable and cannot be added to the match.');
        }
    }
}

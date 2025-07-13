<?php

declare(strict_types=1);

namespace App\Rules\TagTeams;

use App\Models\TagTeams\TagTeam;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class IsBookable implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $tagTeam = TagTeam::find($value);

        if (! $tagTeam instanceof TagTeam || ! $tagTeam->isBookable()) {
            $fail('This tag team is not available for booking.');
        }
    }
}

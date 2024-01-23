<?php

declare(strict_types=1);

namespace App\Rules;

use App\Models\Wrestler;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class WrestlerCanJoinNewTagTeam implements ValidationRule
{
    /**
     * Determine if the validation rule passes.
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        /** @var \App\Models\Wrestler $wrestler */
        $wrestler = Wrestler::query()->with(['currentEmployment', 'futureEmployment'])->whereKey($value)->sole();

        if ($wrestler->isSuspended() || $wrestler->isInjured()) {
            $fail('This wrestler cannot join the tag team.');
        }

        if (($wrestler->currentTagTeam !== null && $wrestler->currentTagTeam->exists())) {
            $fail('This wrestler cannot join the tag team.');
        }
    }
}

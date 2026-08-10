<?php

declare(strict_types=1);

namespace App\Rules\Wrestlers;

use App\Models\Wrestlers\Wrestler;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class CanJoinTagTeam implements ValidationRule
{
    public function __construct(private int|string|null $tagTeamId = null) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $wrestler = Wrestler::query()->find($value);

        if (! $wrestler) {
            $fail('The selected wrestler is invalid.');

            return;
        }

        if ($wrestler->isSuspended() || $wrestler->isInjured()) {
            $fail('This wrestler cannot join the tag team.');

            return;
        }

        $currentTagTeam = $wrestler->currentTagTeam();

        if ($this->tagTeamId !== null) {
            $currentTagTeam->whereKeyNot($this->tagTeamId);
        }

        if ($currentTagTeam->exists()) {
            $fail('This wrestler is already a member of another tag team.');
        }
    }
}

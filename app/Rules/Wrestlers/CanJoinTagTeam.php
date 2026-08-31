<?php

declare(strict_types=1);

namespace App\Rules\Wrestlers;

use App\Models\Roster\Wrestlers\Wrestler;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class CanJoinTagTeam implements ValidationRule
{
    public function __construct(private int|string|null $tagTeamId = null) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_int($value) && ! is_string($value)) {
            $fail('The selected wrestler is invalid.');

            return;
        }

        $wrestler = Wrestler::query()->whereKey($value)->first();

        if (! $wrestler) {
            $fail('The selected wrestler is invalid.');

            return;
        }

        if ($wrestler->currentSuspension()->exists() || $wrestler->currentInjury()->exists()) {
            $fail('This wrestler cannot join the tag team.');

            return;
        }

        $currentTagTeams = $wrestler->tagTeams()
            ->wherePivotNull('left_at');

        if ($this->tagTeamId !== null) {
            $currentTagTeams->where('tag_teams.id', '!=', $this->tagTeamId);
        }

        if ($currentTagTeams->exists()) {
            $fail('This wrestler is already a member of another tag team.');
        }
    }
}

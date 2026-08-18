<?php

declare(strict_types=1);

namespace App\Rules\Wrestlers;

use App\Models\Roster\Wrestlers\Wrestler;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Collection;

class NotRepresentedBySelectedTagTeam implements ValidationRule
{
    /**
     * @param  Collection<int, int>  $tagTeamIds
     */
    public function __construct(private Collection $tagTeamIds) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if ($this->tagTeamIds->isEmpty()) {
            return;
        }

        if (! is_int($value) && ! is_string($value)) {
            $fail('The selected wrestler is invalid.');

            return;
        }

        $wrestler = Wrestler::query()->whereKey($value)->first();

        if (! $wrestler instanceof Wrestler) {
            $fail('The selected wrestler is invalid.');

            return;
        }

        $currentTagTeam = $wrestler->currentTagTeam()->first();

        if (! $currentTagTeam) {
            return;
        }

        $currentTagTeamKey = $currentTagTeam->getKey();

        if ((is_int($currentTagTeamKey) || is_string($currentTagTeamKey)) && $this->tagTeamIds->contains($currentTagTeamKey)) {
            $fail('This wrestler is already represented in the stable through their tag team.');
        }
    }
}

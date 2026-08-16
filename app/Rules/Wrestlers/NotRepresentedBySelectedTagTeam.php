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

        $wrestler = Wrestler::query()->findOrFail($value);
        $currentTagTeam = $wrestler->currentTagTeam()->first();

        if ($currentTagTeam && $this->tagTeamIds->contains($currentTagTeam->getKey())) {
            $fail('This wrestler is already represented in the stable through their tag team.');
        }
    }
}

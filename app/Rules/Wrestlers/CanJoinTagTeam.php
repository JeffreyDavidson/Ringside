<?php

declare(strict_types=1);

namespace App\Rules\Wrestlers;

use App\Models\TagTeams\TagTeam;
use App\Models\Wrestlers\Wrestler;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Database\Eloquent\Builder;

class CanJoinTagTeam implements ValidationRule
{
    public function __construct(private ?TagTeam $tagTeam = null) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        /** @var Wrestler $wrestler */
        $wrestler = Wrestler::with(['currentEmployment', 'futureEmployment'])->findOrFail($value);

        // Check availability
        if ($wrestler->isSuspended() || $wrestler->isInjured()) {
            $fail('This wrestler cannot join the tag team.');

            return;
        }

        // For existing tag teams
        if ($this->tagTeam) {
            if ($this->tagTeam->currentWrestlers->contains($wrestler)) {
                $fail('This wrestler is already a member of this tag team.');

                return;
            }

            // Check for membership in other bookable tag teams
            $isInOtherTagTeam = TagTeam::query()
                ->bookable()
                ->whereNotIn('id', [$this->tagTeam->id])
                ->whereHas('currentWrestlers', function (Builder $query) use ($wrestler): void {
                    $query->where('wrestler_id', $wrestler->id);
                })
                ->exists();

            if ($isInOtherTagTeam) {
                $fail('This wrestler is already a member of another tag team.');

                return;
            }
        } else {
            // For new tag teams
            if ($wrestler->currentTagTeam()->first() !== null) {
                $fail('This wrestler is already a member of another tag team.');
            }
        }
    }
}

<?php

declare(strict_types=1);

namespace App\Rules\Stables;

use App\Models\Stables\Stable;
use App\Models\TagTeams\TagTeam;
use App\Models\Wrestlers\Wrestler;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Collection;

class HasMinimumMembers implements ValidationRule
{
    /**
     * @param  Collection<int, Wrestler>  $wrestlers
     * @param  Collection<int, TagTeam>  $tagTeams
     */
    public function __construct(
        private readonly Collection $wrestlers,
        private readonly Collection $tagTeams
    ) {}

    /**
     * Determine if the validation rule passes.
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $tagTeamsCountFromRequest = $this->tagTeams->count();
        $wrestlersCountFromRequest = $this->wrestlers->count();

        // Each tag team counts as 2 members (minimum for a tag team)
        $tagTeamMembersCount = $tagTeamsCountFromRequest * 2;
        $totalMembersCount = $tagTeamMembersCount + $wrestlersCountFromRequest;

        if ($totalMembersCount < Stable::MIN_MEMBERS_COUNT) {
            $fail('A stable must have at least '.Stable::MIN_MEMBERS_COUNT." members. Currently adding {$totalMembersCount} members.");
        }
    }
}

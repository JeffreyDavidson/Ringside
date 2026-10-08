<?php

declare(strict_types=1);

namespace App\Rules\Stables;

use App\Data\Stables\StableMembershipData;
use App\Lifecycle\Roster\Stables\StableMembershipRequirements;
use App\Models\Roster\TagTeams\TagTeam;
use App\Models\Roster\Wrestlers\Wrestler;
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
        $members = new StableMembershipData($this->wrestlers, $this->tagTeams);
        $totalMembersCount = $members->getTotalMemberCount();

        if (! StableMembershipRequirements::hasMinimumHeadcount($totalMembersCount)) {
            $fail(__('stables.validation.minimum_members', ['minimum' => StableMembershipRequirements::MINIMUM_MEMBER_COUNT, 'count' => $totalMembersCount]));
        }
    }
}

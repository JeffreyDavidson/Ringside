<?php

declare(strict_types=1);

namespace App\Actions\TagTeams;

use App\Models\TagTeams\TagTeam;
use App\Models\Wrestlers\Wrestler;
use App\Services\TagTeamMembershipService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Lorisleiva\Actions\Concerns\AsAction;

class AddTagTeamPartnersAction
{
    use AsAction;

    /**
     * Create a new add tag team partners action instance.
     */
    public function __construct(
        protected TagTeamMembershipService $membershipService
    ) {}

    /**
     * Add new partners to an existing tag team using the membership service.
     *
     * This action serves as a focused interface for adding partners while delegating
     * all complex business logic, validation, and data management to the centralized
     * TagTeamMembershipService. This ensures consistency across all partnership
     * operations and eliminates code duplication.
     *
     * @param  TagTeam  $tagTeam  The tag team to add partners to
     * @param  Collection<int, Wrestler>  $wrestlers  Collection of wrestlers to add as partners
     * @param  Carbon|null  $joinDate  The partnership start date (defaults to now)
     * @param  bool  $employIfNeeded  Whether to employ unemployed wrestlers (default: false)
     * @return Collection<int, Wrestler> Collection of actually added partners
     *
     * @example
     * ```php
     * // Add partners without employment
     * $wrestlers = collect([$wrestler1, $wrestler2]);
     * AddTagTeamPartnersAction::run($tagTeam, $wrestlers);
     *
     * // Add partners with automatic employment
     * $addedPartners = AddTagTeamPartnersAction::run($tagTeam, $wrestlers, now(), true);
     *
     * // Add partners with specific join date
     * AddTagTeamPartnersAction::run($tagTeam, $wrestlers, Carbon::parse('2024-01-01'));
     * ```
     */
    public function handle(
        TagTeam $tagTeam,
        Collection $wrestlers,
        ?Carbon $joinDate = null,
        bool $employIfNeeded = false
    ): Collection {
        $joinDate = $joinDate ?? now();

        return DB::transaction(function () use ($tagTeam, $wrestlers, $joinDate, $employIfNeeded): Collection {
            return $this->membershipService->addPartners(
                $tagTeam,
                $wrestlers,
                $joinDate,
                $employIfNeeded
            );
        });
    }
}

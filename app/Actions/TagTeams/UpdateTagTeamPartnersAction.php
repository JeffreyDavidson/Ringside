<?php

declare(strict_types=1);

namespace App\Actions\TagTeams;

use App\Models\TagTeams\TagTeam;
use App\Models\Wrestlers\Wrestler;
use App\Services\TagTeamMembershipService;
use App\Support\DateHelper;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Lorisleiva\Actions\Concerns\AsAction;

class UpdateTagTeamPartnersAction
{
    use AsAction;

    /**
     * Create a new update tag team partners action instance.
     */
    public function __construct(
        protected TagTeamMembershipService $membershipService
    ) {}

    /**
     * Update tag team partnership composition using the membership service.
     *
     * This action serves as a focused interface for updating partnerships while
     * delegating all complex business logic, validation, and data management to
     * the centralized TagTeamMembershipService. This ensures consistency across
     * all partnership operations and eliminates code duplication.
     *
     * @param  TagTeam  $tagTeam  The tag team to update partnerships for
     * @param  Collection<int, Wrestler>  $wrestlers  Collection of wrestlers for the updated partnership
     * @param  Carbon|null  $updateDate  The partnership change date (defaults to now)
     * @param  bool  $employIfNeeded  Whether to employ unemployed new wrestlers (default: false)
     * @return Collection<int, Wrestler> Collection of newly added partners
     *
     * @example
     * ```php
     * // Update partners without employment
     * $newPartners = collect([$wrestler1, $wrestler2]);
     * UpdateTagTeamPartnersAction::run($tagTeam, $newPartners);
     *
     * // Update partners with automatic employment
     * $addedPartners = UpdateTagTeamPartnersAction::run($tagTeam, $newPartners, now(), true);
     *
     * // Update with specific change date
     * UpdateTagTeamPartnersAction::run($tagTeam, $newPartners, Carbon::parse('2024-01-01'));
     * ```
     */
    public function handle(
        TagTeam $tagTeam,
        Collection $wrestlers,
        ?Carbon $updateDate = null,
        bool $employIfNeeded = false
    ): Collection {
        $updateDate = DateHelper::resolveDate($updateDate);

        return DB::transaction(function () use ($tagTeam, $wrestlers, $updateDate, $employIfNeeded): Collection {
            return $this->membershipService->updatePartnerships(
                $tagTeam,
                $wrestlers,
                $updateDate,
                $employIfNeeded
            );
        });
    }
}

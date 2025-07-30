<?php

declare(strict_types=1);

namespace App\Actions\TagTeams;

use App\Actions\Managers\RetireAction as ManagersRetireAction;
use App\Actions\Wrestlers\RetireAction as WrestlersRetireAction;
use App\Enums\Shared\EmploymentStatus;
use App\Exceptions\Roster\TagTeams\CannotBeRetiredException;
use App\Models\TagTeams\TagTeam;
use App\Support\DateHelper;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Lorisleiva\Actions\Concerns\AsAction;

class RetireAction
{
    use AsAction;

    /**
     * Create a new retire action instance.
     */
    public function __construct(
        protected WrestlersRetireAction $wrestlersRetireAction,
        protected ManagersRetireAction $managersRetireAction
    ) {}

    /**
     * Retire a tag team and end their partnership.
     *
     * This handles the complete tag team retirement workflow with flexible options:
     * - Validates the tag team can be retired (business rule compliance)
     * - Ends current employment and suspension if active
     * - Optionally retires available partners and managers
     * - Creates retirement record to formally end the partnership
     * - Makes the tag team permanently unavailable for competition
     * - Preserves all historical records and championship lineage
     * - Individual members may continue their careers independently
     *
     * @param  TagTeam  $tagTeam  The tag team to retire
     * @param  Carbon|null  $retirementDate  The retirement date (defaults to now)
     * @param  bool  $retirePartners  Whether to retire available partners (default: true)
     * @throws CannotBeRetiredException When tag team cannot be retired due to business rules
     *
     * @example
     * ```php
     * // Retire tag team immediately
     * $tagTeam = TagTeam::where('name', 'The Undertakers')->first();
     * RetireAction::run($tagTeam);
     *
     * // Retire with specific date
     * RetireAction::run($tagTeam, Carbon::parse('2024-12-31'));
     *
     * // Retire without retiring partners (partners continue independently)
     * RetireAction::run($tagTeam, retirePartners: false);
     * ```
     */
    public function handle(TagTeam $tagTeam, ?Carbon $retirementDate = null, bool $retirePartners = true): void
    {
        $tagTeam->ensureCanBeRetired();

        $retirementDate = DateHelper::resolveDate($retirementDate);

        DB::transaction(function () use ($tagTeam, $retirementDate, $retirePartners): void {
            // End current employment if employed
            if ($tagTeam->isEmployed()) {
                $tagTeam->employments()->where('ended_at', null)->update(['ended_at' => $retirementDate]);
            }

            // End current suspension if suspended
            if ($tagTeam->isSuspended()) {
                $tagTeam->suspensions()->where('ended_at', null)->update(['ended_at' => $retirementDate]);
            }

            // Retire current partners if requested
            if ($retirePartners) {
                $partnersToRetire = $tagTeam->currentWrestlers
                    ->filter(fn ($wrestler) => $wrestler->canBeRetired());

                foreach ($partnersToRetire as $wrestler) {
                    $this->wrestlersRetireAction->handle($wrestler, $retirementDate);
                }

                // Retire current managers
                $managersToRetire = $tagTeam->currentManagers
                    ->filter(fn ($manager) => $manager->canBeRetired());

                foreach ($managersToRetire as $manager) {
                    $this->managersRetireAction->handle($manager, $retirementDate);
                }
            }

            // Create retirement record
            $tagTeam->retirements()->create([
                'retired_at' => $retirementDate,
            ]);

            // Update status to retired
            $tagTeam->update(['status' => EmploymentStatus::Retired]);
        });
    }
}

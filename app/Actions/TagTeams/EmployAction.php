<?php

declare(strict_types=1);

namespace App\Actions\TagTeams;

use App\Actions\Managers\EmployAction as ManagersEmployAction;
use App\Actions\Wrestlers\EmployAction as WrestlersEmployAction;
use App\Enums\Shared\EmploymentStatus;
use App\Exceptions\Roster\TagTeams\CannotBeEmployedException;
use App\Models\TagTeams\TagTeam;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Lorisleiva\Actions\Concerns\AsAction;

class EmployAction
{
    use AsAction;

    /**
     * Create a new employ action instance.
     */
    public function __construct(
        protected WrestlersEmployAction $wrestlersEmployAction,
        protected ManagersEmployAction $managersEmployAction
    ) {}

    /**
     * Employ a tag team.
     *
     * This handles the complete tag team employment workflow:
     * - Validates the tag team can be employed (business rule compliance)
     * - Ends retirement if currently retired
     * - Creates an employment record for the tag team
     * - Employs all current wrestlers who aren't already employed
     * - Employs all current managers who aren't already employed
     * - Makes the tag team available for match bookings and championships
     * - Maintains employment consistency across all team members
     *
     * @param  TagTeam  $tagTeam  The tag team to employ
     * @param  Carbon|null  $employmentDate  The employment start date (defaults to now)
     * @throws CannotBeEmployedException When tag team cannot be employed due to business rules
     *
     * @example
     * ```php
     * // Employ tag team immediately
     * $tagTeam = TagTeam::where('name', 'The Young Bucks')->first();
     * EmployAction::run($tagTeam);
     *
     * // Employ with specific start date
     * EmployAction::run($tagTeam, Carbon::parse('2024-01-01'));
     * ```
     */
    public function handle(TagTeam $tagTeam, ?Carbon $employmentDate = null): void
    {
        $tagTeam->ensureCanBeEmployed();

        $employmentDate = $employmentDate ?? now();

        DB::transaction(function () use ($tagTeam, $employmentDate): void {
            // End retirement if currently retired
            if ($tagTeam->isRetired()) {
                $tagTeam->retirements()->where('ended_at', null)->update(['ended_at' => $employmentDate]);
            }

            // Create employment record
            $tagTeam->employments()->create([
                'started_at' => $employmentDate,
                'ended_at' => null,
            ]);

            // Update status to employed
            $tagTeam->update(['status' => EmploymentStatus::Employed]);

            // Employ current wrestlers if they're not already employed
            $currentWrestlers = $tagTeam->currentWrestlers
                ->filter(fn ($wrestler) => ! $wrestler->isEmployed());

            foreach ($currentWrestlers as $wrestler) {
                $this->wrestlersEmployAction->handle($wrestler, $employmentDate);
            }

            // Employ current managers if they're not already employed
            $currentManagers = $tagTeam->currentManagers
                ->filter(fn ($manager) => ! $manager->isEmployed());

            foreach ($currentManagers as $manager) {
                $this->managersEmployAction->handle($manager, $employmentDate);
            }
        });
    }
}

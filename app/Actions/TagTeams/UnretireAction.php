<?php

declare(strict_types=1);

namespace App\Actions\TagTeams;

use App\Actions\Managers\UnretireAction as ManagersUnretireAction;
use App\Actions\Wrestlers\UnretireAction as WrestlersUnretireAction;
use App\Enums\Shared\EmploymentStatus;
use App\Exceptions\Roster\CannotBeUnretiredException;
use App\Models\Managers\Manager;
use App\Models\TagTeams\TagTeam;
use App\Models\Wrestlers\Wrestler;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Lorisleiva\Actions\Concerns\AsAction;

class UnretireAction
{
    use AsAction;

    /**
     * Create a new unretire action instance.
     */
    public function __construct(
        protected WrestlersUnretireAction $wrestlersUnretireAction,
        protected ManagersUnretireAction $managersUnretireAction
    ) {}

    /**
     * Unretire a retired tag team and return them to active competition.
     *
     * This handles the complete tag team comeback workflow:
     * - Validates the tag team can come out of retirement (currently retired)
     * - Ends the current retirement period with the specified date
     * - Creates a new employment record starting from the unretirement date
     * - Restores the tag team to available status for match bookings
     * - Makes the team available for championship opportunities again
     * - Preserves all historical retirement and partnership records
     * - Unretires current wrestlers and managers who were retired with the team
     *
     * @param  TagTeam  $tagTeam  The tag team to unretire
     * @param  Carbon|null  $unretiredDate  The unretirement date (defaults to now)
     * @throws CannotBeUnretiredException When tag team cannot be unretired due to business rules
     *
     * @example
     * ```php
     * // Unretire tag team immediately
     * $tagTeam = TagTeam::where('name', 'The Hardy Boyz')->first();
     * UnretireAction::run($tagTeam);
     *
     * // Unretire with specific date
     * UnretireAction::run($tagTeam, Carbon::parse('2024-01-01'));
     * ```
     */
    public function handle(TagTeam $tagTeam, ?Carbon $unretiredDate = null): void
    {
        $tagTeam->ensureCanBeUnretired();

        $unretiredDate = $unretiredDate ?? now();

        DB::transaction(function () use ($tagTeam, $unretiredDate): void {
            // End the current retirement record
            $tagTeam->employments()->where('ended_at', null)->update(['ended_at' => $unretiredDate]);

            // Unretire current wrestlers and managers who were retired with the team
            $wrestlersToUnretire = $tagTeam->currentWrestlers
                ->filter(fn (Wrestler $wrestler) => $wrestler->isRetired());
            $managersToUnretire = $tagTeam->currentManagers
                ->filter(fn (Manager $manager) => $manager->isRetired());

            // Unretire the provided wrestlers
            $wrestlersToUnretire->each(fn (Wrestler $wrestler) => $this->wrestlersUnretireAction->handle($wrestler, $unretiredDate));

            // Unretire the provided managers
            $managersToUnretire->each(fn (Manager $manager) => $this->managersUnretireAction->handle($manager, $unretiredDate));

            // Create a new employment record starting from the unretirement date
            $tagTeam->employments()->create([
                'started_at' => $unretiredDate,
                'ended_at' => null,
                'status' => EmploymentStatus::Employed,
            ]);
        });
    }
}

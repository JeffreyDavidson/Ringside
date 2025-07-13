<?php

declare(strict_types=1);

namespace App\Actions\TagTeams;

use App\Actions\Managers\EmployAction as ManagersEmployAction;
use App\Actions\Wrestlers\EmployAction as WrestlersEmployAction;
use App\Data\TagTeams\TagTeamData;
use App\Models\Managers\Manager;
use App\Models\TagTeams\TagTeam;
use App\Models\Wrestlers\Wrestler;
use App\Repositories\TagTeamRepository;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Lorisleiva\Actions\Concerns\AsAction;

class UpdateAction extends BaseTagTeamAction
{
    use AsAction;

    /**
     * Create a new update action instance.
     */
    public function __construct(
        protected TagTeamRepository $tagTeamRepository,
        protected WrestlersEmployAction $wrestlersEmployAction,
        protected ManagersEmployAction $managersEmployAction
    ) {
        parent::__construct($tagTeamRepository);
    }

    /**
     * Update a tag team.
     *
     * This handles the complete tag team update workflow:
     * - Updates tag team information (name, signature moves, etc.)
     * - Manages wrestler changes (replacing current partners with new ones)
     * - Manages manager changes (adding/removing managers)
     * - Handles conditional employment for new members
     * - Maintains data integrity throughout the update process
     * - Preserves partnership history with proper date tracking
     *
     * @param  TagTeam  $tagTeam  The tag team to update
     * @param  TagTeamData  $tagTeamData  The updated tag team information
     * @return TagTeam The updated tag team instance
     *
     * @example
     * ```php
     * // Update tag team name only
     * $tagTeamData = new TagTeamData([
     *     'name' => 'The New Day (Updated)'
     * ]);
     * $updatedTeam = UpdateAction::run($tagTeam, $tagTeamData);
     *
     * // Change partners and employ unemployed tag team
     * $tagTeamData = new TagTeamData([
     *     'wrestlerA' => $kofi,
     *     'wrestlerB' => $bigE,
     *     'employment_date' => Carbon::parse('2024-01-01')
     * ]);
     * $updatedTeam = UpdateAction::run($unemployedTeam, $tagTeamData);
     * ```
     */
    public function handle(TagTeam $tagTeam, TagTeamData $tagTeamData): TagTeam
    {
        return DB::transaction(function () use ($tagTeam, $tagTeamData): TagTeam {
            // Update the tag team's basic information
            $this->tagTeamRepository->update($tagTeam, $tagTeamData);
            $updateDate = $this->getEffectiveDate();

            // Handle member changes (wrestlers and managers)
            $newWrestlers = $this->updateWrestlerPartnerships($tagTeam, [$tagTeamData->wrestlerA, $tagTeamData->wrestlerB], $updateDate);
            $managersToAdd = $this->updateManagerRelationships($tagTeam, $tagTeamData->managers ?? [], $updateDate);

            // Employ newly added members if employment date is provided
            $this->handleMemberEmployment($newWrestlers, $managersToAdd, $tagTeamData->employment_date);

            // Create employment record if employment_date is provided and tag team is eligible
            if (! is_null($tagTeamData->employment_date) && ! $tagTeam->isEmployed()) {
                $this->tagTeamRepository->createEmployment($tagTeam, $tagTeamData->employment_date);
            }

            return $tagTeam;
        });
    }

    /**
     * Update wrestler partnerships by replacing current partners with new ones.
     *
     * @param  TagTeam  $tagTeam  The tag team to update
     * @param  array<int, Wrestler|null>  $newWrestlers  Array of new wrestlers [wrestlerA, wrestlerB]
     * @param  Carbon  $updateDate  The date of the partnership change
     * @return Collection<int, Wrestler> Collection of newly added wrestlers
     */
    private function updateWrestlerPartnerships(TagTeam $tagTeam, array $newWrestlers, Carbon $updateDate): Collection
    {
        $newWrestlersCollection = collect($newWrestlers)
            ->filter() // Remove null values
            ->ensure(Wrestler::class);

        // Remove current wrestlers who are not in the new list
        $currentWrestlers = $tagTeam->currentWrestlers;
        $wrestlersToRemove = $currentWrestlers->diff($newWrestlersCollection);

        if ($wrestlersToRemove->isNotEmpty()) {
            $this->tagTeamRepository->removeWrestlers($tagTeam, $wrestlersToRemove, $updateDate);
        }

        // Add new wrestlers who are not currently in the team
        $wrestlersToAdd = $newWrestlersCollection->diff($currentWrestlers);

        if ($wrestlersToAdd->isNotEmpty()) {
            $this->tagTeamRepository->addWrestlers($tagTeam, $wrestlersToAdd, $updateDate);
        }

        return $wrestlersToAdd->values();
    }

    /**
     * Update manager relationships by adding/removing managers as needed.
     *
     * @param  TagTeam  $tagTeam  The tag team to update
     * @param  Collection<int, Manager>|array<int, Manager>  $newManagers  Collection or array of new managers
     * @param  Carbon  $updateDate  The date of the relationship change
     * @return Collection<int, Manager> Collection of newly added managers
     */
    private function updateManagerRelationships(TagTeam $tagTeam, Collection|array $newManagers, Carbon $updateDate): Collection
    {
        $newManagersCollection = collect($newManagers)
            ->ensure(Manager::class);

        // Remove current managers who are not in the new list
        $currentManagers = $tagTeam->currentManagers;
        $managersToRemove = $currentManagers->diff($newManagersCollection);

        if ($managersToRemove->isNotEmpty()) {
            $this->tagTeamRepository->removeManagers($tagTeam, $managersToRemove, $updateDate);
        }

        // Add new managers who are not currently managing the team
        $managersToAdd = $newManagersCollection->diff($currentManagers);

        if ($managersToAdd->isNotEmpty()) {
            $this->tagTeamRepository->addManagers($tagTeam, $managersToAdd, $updateDate);
        }

        return $managersToAdd->values();
    }

    /**
     * Handle employment for newly added wrestlers and managers.
     *
     * @param  Collection<int, Wrestler>  $newWrestlers
     * @param  Collection<int, Manager>  $managersToAdd
     */
    private function handleMemberEmployment(Collection $newWrestlers, Collection $managersToAdd, ?Carbon $employmentDate): void
    {
        if (! isset($employmentDate)) {
            return;
        }

        // Employ newly added wrestlers if they're not already employed
        $newWrestlers
            ->filter(fn (Wrestler $wrestler) => ! $wrestler->isEmployed())
            ->each(fn (Wrestler $wrestler) => $this->wrestlersEmployAction->handle($wrestler, $employmentDate));

        // Employ newly added managers if they're not already employed
        $managersToAdd
            ->filter(fn (Manager $manager) => ! $manager->isEmployed())
            ->each(fn (Manager $manager) => $this->managersEmployAction->handle($manager, $employmentDate));
    }
}

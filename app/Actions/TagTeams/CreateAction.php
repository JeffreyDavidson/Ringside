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

class CreateAction extends BaseTagTeamAction
{
    use AsAction;

    /**
     * Create a new create action instance.
     */
    public function __construct(
        protected TagTeamRepository $tagTeamRepository,
        protected WrestlersEmployAction $wrestlersEmployAction,
        protected ManagersEmployAction $managersEmployAction
    ) {
        parent::__construct($tagTeamRepository);
    }

    /**
     * Create a tag team.
     *
     * This handles the complete tag team creation workflow:
     * - Creates the tag team record with name and signature moves
     * - Adds two wrestlers (wrestlerA and wrestlerB) as founding partners
     * - Assigns managers if provided
     * - Creates employment records if employment_date is specified
     * - Ensures all members are properly employed
     *
     * @param  TagTeamData  $tagTeamData  The data transfer object containing tag team information
     * @return TagTeam The newly created tag team with all members
     *
     * @example
     * ```php
     * // Create tag team with immediate employment
     * $tagTeamData = new TagTeamData([
     *     'name' => 'The Hardy Boyz',
     *     'wrestlerA' => $matt,
     *     'wrestlerB' => $jeff,
     *     'managers' => [$lita],
     *     'employment_date' => now()
     * ]);
     * $tagTeam = CreateAction::run($tagTeamData);
     *
     * // Create tag team without employment (must be employed separately)
     * $tagTeamData = new TagTeamData([
     *     'name' => 'The New Day',
     *     'wrestlerA' => $kofi,
     *     'wrestlerB' => $xavier
     * ]);
     * $tagTeam = CreateAction::run($tagTeamData);
     * ```
     */
    public function handle(TagTeamData $tagTeamData): TagTeam
    {
        return DB::transaction(function () use ($tagTeamData): TagTeam {
            // Create the base tag team record
            $tagTeam = $this->tagTeamRepository->create($tagTeamData);
            $datetime = $this->getEffectiveDate();

            // Prepare member collections
            $wrestlers = collect([$tagTeamData->wrestlerA, $tagTeamData->wrestlerB])->filter();
            $managers = $tagTeamData->managers ?? collect();

            // Add founding members to the tag team
            $this->addWrestlersToTeam($tagTeam, $wrestlers, $datetime);
            $this->addManagersToTeam($tagTeam, $managers, $datetime);

            // Handle employment for tag team and all members if employment_date provided
            $this->handleEmployment($tagTeam, $wrestlers->filter(), $managers, $tagTeamData->employment_date);

            return $tagTeam;
        });
    }

    /**
     * Add wrestlers to the tag team.
     *
     * @param  Collection<int, Wrestler>  $wrestlers
     */
    private function addWrestlersToTeam(TagTeam $tagTeam, Collection $wrestlers, Carbon $datetime): void
    {
        $wrestlersCollection = $wrestlers
            ->ensure(Wrestler::class)
            ->values(); // Reset keys to be sequential integers

        if ($wrestlersCollection->isNotEmpty()) {
            // Convert to Eloquent Collection if needed
            $eloquentCollection = new \Illuminate\Database\Eloquent\Collection($wrestlersCollection->all());
            $this->tagTeamRepository->addWrestlers($tagTeam, $eloquentCollection, $datetime);
        }
    }

    /**
     * Add managers to the tag team.
     *
     * @param  Collection<int, Manager>|null  $managers
     */
    private function addManagersToTeam(TagTeam $tagTeam, ?Collection $managers, Carbon $datetime): void
    {
        $managersCollection = collect($managers)
            ->ensure(Manager::class)
            ->values(); // Reset keys to be sequential integers

        $managersCollection->whenNotEmpty(fn ($managers) => $this->tagTeamRepository->addManagers($tagTeam, $managers, $datetime));
    }

    /**
     * Handle employment for the tag team and its members.
     *
     * @param  Collection<int, Wrestler>  $wrestlers
     * @param  Collection<int, Manager>  $managers
     */
    private function handleEmployment(TagTeam $tagTeam, Collection $wrestlers, Collection $managers, ?Carbon $employmentDate): void
    {
        if (! isset($employmentDate)) {
            return;
        }

        $this->tagTeamRepository->createEmployment($tagTeam, $employmentDate);

        // Employ wrestlers if they're not already employed
        $wrestlers
            ->filter(fn (Wrestler $wrestler) => ! $wrestler->isEmployed())
            ->each(fn (Wrestler $wrestler) => $this->wrestlersEmployAction->handle($wrestler, $employmentDate));

        // Employ managers if they're not already employed
        $managers
            ->filter(fn (Manager $manager) => ! $manager->isEmployed())
            ->each(fn (Manager $manager) => $this->managersEmployAction->handle($manager, $employmentDate));
    }
}

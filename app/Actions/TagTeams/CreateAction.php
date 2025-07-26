<?php

declare(strict_types=1);

namespace App\Actions\TagTeams;

use App\Actions\Managers\EmployAction as ManagersEmployAction;
use App\Actions\Wrestlers\EmployAction as WrestlersEmployAction;
use App\Data\TagTeams\TagTeamData;
use App\Enums\Shared\EmploymentStatus;
use App\Models\Managers\Manager;
use App\Models\TagTeams\TagTeam;
use App\Models\Wrestlers\Wrestler;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Lorisleiva\Actions\Concerns\AsAction;

class CreateAction
{
    use AsAction;

    /**
     * Create a new create action instance.
     */
    public function __construct(
        protected WrestlersEmployAction $wrestlersEmployAction,
        protected ManagersEmployAction $managersEmployAction
    ) {}

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
            $tagTeam = TagTeam::query()->create([
                'name' => $tagTeamData->name,
                'signature_move' => $tagTeamData->signature_move,
            ]);

            $datetime = now();

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
            $eloquentCollection->each(function (Wrestler $wrestler) use ($tagTeam, $datetime): void {
                $tagTeam->wrestlers()->attach($wrestler->getKey(), [
                    'joined_at' => $datetime->toDateTimeString(),
                ]);
            });
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

        $managersCollection->whenNotEmpty(function (Collection $managers) use ($tagTeam, $datetime) {
            $managers->each(function (Manager $manager) use ($tagTeam, $datetime): void {
                $tagTeam->managers()->attach($manager->getKey(), [
                    'hired_at' => $datetime->toDateTimeString(),
                ]);
            });
        });
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

        // Create or update the employment relationship
        $tagTeam->employments()->updateOrCreate(
            ['ended_at' => null],
            ['started_at' => $employmentDate->toDateTimeString()]
        );

        // Update the status field to reflect employment
        $tagTeam->update(['status' => EmploymentStatus::Employed]); // @phpstan-ignore-line method.notFound

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

<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Builders\Roster\WrestlerBuilder;
use App\Data\Wrestlers\WrestlerData;
use App\Enums\Shared\EmploymentStatus;
use App\Models\Managers\Manager;
use App\Models\Stables\StableMember;
use App\Models\TagTeams\TagTeam;
use App\Models\Wrestlers\Wrestler;
use App\Models\Wrestlers\WrestlerEmployment;
use App\Models\Wrestlers\WrestlerInjury;
use App\Models\Wrestlers\WrestlerRetirement;
use App\Models\Wrestlers\WrestlerSuspension;
use App\Repositories\Concerns\ManagesDates;
use App\Repositories\Concerns\ManagesEmployment;
use App\Repositories\Concerns\ManagesInjury;
use App\Repositories\Concerns\ManagesMembers;
use App\Repositories\Concerns\ManagesRetirement;
use App\Repositories\Concerns\ManagesSuspension;
use App\Repositories\Contracts\ManagesEmployment as ManagesEmploymentContract;
use App\Repositories\Contracts\ManagesInjury as ManagesInjuryContract;
use App\Repositories\Contracts\ManagesRetirement as ManagesRetirementContract;
use App\Repositories\Contracts\ManagesSuspension as ManagesSuspensionContract;
use App\Repositories\Contracts\ManagesWrestlerRelations;
use App\Repositories\Contracts\WrestlerRepositoryInterface;
use App\Repositories\Support\BaseRepository;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Tests\Unit\Repositories\WrestlerRepositoryTest;

/**
 * Repository for managing wrestler data and operations.
 *
 * Handles wrestler-specific data operations including employment tracking,
 * injury management, tag team memberships, retirement, suspensions, and
 *
 * @see WrestlerRepositoryTest
 * period-based date operations. Provides a clean abstraction layer between
 * controllers/services and wrestler models.
 *
 * @author Your Name
 *
 * @since 1.0.0
 */
class WrestlerRepository extends BaseRepository implements ManagesEmploymentContract, ManagesInjuryContract, ManagesRetirementContract, ManagesSuspensionContract, ManagesWrestlerRelations, WrestlerRepositoryInterface
{
    /** @use ManagesDates<Wrestler, WrestlerEmployment> */
    use ManagesDates;

    /** @use ManagesEmployment<WrestlerEmployment, Wrestler> */
    use ManagesEmployment;

    /** @use ManagesInjury<WrestlerInjury, Wrestler> */
    use ManagesInjury;

    /** @use ManagesMembers<StableMember, Wrestler> */
    use ManagesMembers;

    /** @use ManagesRetirement<WrestlerRetirement, Wrestler> */
    use ManagesRetirement;

    /** @use ManagesSuspension<WrestlerSuspension, Wrestler> */
    use ManagesSuspension;

    /**
     * Create a new wrestler.
     *
     * Creates a new wrestler record with the provided data and returns
     * the created model instance for further operations.
     *
     * @param  WrestlerData  $wrestlerData  The wrestler data transfer object
     * @return Wrestler The created wrestler model
     */
    public function create(WrestlerData $wrestlerData): Wrestler
    {
        /** @var Wrestler $wrestler */
        $wrestler = Wrestler::query()->create([
            'name' => $wrestlerData->name,
            'height' => $wrestlerData->height,
            'weight' => $wrestlerData->weight,
            'hometown' => $wrestlerData->hometown,
            'signature_move' => $wrestlerData->signature_move,
        ]);

        return $wrestler;
    }

    /**
     * Update a wrestler.
     *
     * Updates an existing wrestler with new data and returns the
     * updated model instance.
     *
     * @param  Wrestler  $wrestler  The wrestler to update
     * @param  WrestlerData  $wrestlerData  The new wrestler data
     * @return Wrestler The updated wrestler model
     */
    public function update(Wrestler $wrestler, WrestlerData $wrestlerData): Wrestler
    {
        $wrestler->update([
            'name' => $wrestlerData->name,
            'height' => $wrestlerData->height,
            'weight' => $wrestlerData->weight,
            'hometown' => $wrestlerData->hometown,
            'signature_move' => $wrestlerData->signature_move,
        ]);

        return $wrestler;
    }

    /**
     * Remove wrestler from their current tag team.
     *
     * Removes a wrestler from their currently active tag team by ending
     * their membership period on the specified date.
     *
     * @param  Wrestler  $wrestler  The wrestler to remove
     * @param  Carbon  $removalDate  The date to end the tag team membership
     */
    public function removeFromCurrentTagTeam(Wrestler $wrestler, Carbon $removalDate): void
    {
        $currentTagTeam = $wrestler->currentTagTeam()->first();

        if ($currentTagTeam) {
            $this->removeCurrentMember($wrestler, 'tagTeams', $currentTagTeam, $removalDate);
        }
    }

    /**
     * Remove wrestler from all current managers.
     *
     * Removes a wrestler from all their currently active manager relationships
     * by ending their management periods on the specified date.
     *
     * @param  Wrestler  $wrestler  The wrestler to remove from managers
     * @param  Carbon  $removalDate  The date to end the manager relationships
     */
    public function removeFromCurrentManagers(Wrestler $wrestler, Carbon $removalDate): void
    {
        $wrestler->currentManagers()->get()->each(function ($manager) use ($wrestler, $removalDate) {
            $this->removeManager($wrestler, $manager, $removalDate);
        });
    }

    /**
     * Remove wrestler from their current stable.
     *
     * Removes a wrestler from their currently active stable by ending
     * their membership period on the specified date.
     *
     * @param  Wrestler  $wrestler  The wrestler to remove
     * @param  Carbon  $removalDate  The date to end the stable membership
     */
    public function removeFromCurrentStable(Wrestler $wrestler, Carbon $removalDate): void
    {
        $currentStable = $wrestler->currentStable;

        if ($currentStable) {
            app(StableRepository::class)->removeWrestler($currentStable, $wrestler, $removalDate);
        }
    }

    /**
     * Start a new period for a wrestler (employment, injury, etc.).
     *
     * Creates a new period record for the wrestler using the ManagesDates
     * trait functionality. This provides a unified interface for starting
     * any type of temporal relationship.
     *
     * @param  Wrestler  $wrestler  The wrestler to start the period for
     * @param  string  $relationship  The relationship name (e.g., 'employments', 'injuries')
     * @param  Carbon  $startDate  When the period starts
     * @param  array<string, mixed>  $additionalData  Additional data for the period record
     * @return Model The created period record
     *
     * @example
     * ```php
     * // Start a new employment period
     * $repository->startWrestlerPeriod(
     *     wrestler: $wrestler,
     *     relationship: 'employments',
     *     startDate: Carbon::now(),
     *     additionalData: ['position' => 'Main Event', 'salary' => 75000]
     * );
     *
     * // Start an injury period
     * $repository->startWrestlerPeriod(
     *     wrestler: $wrestler,
     *     relationship: 'injuries',
     *     startDate: Carbon::now(),
     *     additionalData: ['type' => 'shoulder', 'severity' => 'moderate']
     * );
     * ```
     */
    public function startWrestlerPeriod(
        Wrestler $wrestler,
        string $relationship,
        Carbon $startDate,
        array $additionalData = []
    ): Model {
        return $this->startPeriod(
            model: $wrestler,
            relationship: $relationship,
            startDate: $startDate,
            additionalData: $additionalData
        );
    }

    /**
     * End the current active period for a wrestler.
     *
     * Terminates a wrestler's current active period (employment, injury, etc.)
     * by setting the end date on the active period record.
     *
     * @param  Wrestler  $wrestler  The wrestler to end the period for
     * @param  string  $currentRelationship  The current period relationship name
     * @param  Carbon  $endDate  When the period ends
     * @return bool True if a period was ended successfully
     *
     * @example
     * ```php
     * // End current employment
     * $repository->endWrestlerPeriod(
     *     wrestler: $wrestler,
     *     currentRelationship: 'currentEmployment',
     *     endDate: Carbon::now()
     * );
     *
     * // End current injury
     * $repository->endWrestlerPeriod(
     *     wrestler: $wrestler,
     *     currentRelationship: 'currentInjury',
     *     endDate: Carbon::now()
     * );
     * ```
     */
    public function endWrestlerPeriod(
        Wrestler $wrestler,
        string $currentRelationship,
        Carbon $endDate
    ): bool {
        return $this->endCurrentPeriod(
            model: $wrestler,
            currentRelationship: $currentRelationship,
            endDate: $endDate
        );
    }

    /**
     * Check if a wrestler has an active period of a specific type.
     *
     * Determines whether the wrestler currently has an active period
     * for the specified relationship type.
     *
     * @param  Wrestler  $wrestler  The wrestler to check
     * @param  string  $currentRelationship  The current period relationship name
     * @return bool True if there's an active period
     *
     * @example
     * ```php
     * // Check if currently employed
     * $isEmployed = $repository->wrestlerHasActivePeriod($wrestler, 'currentEmployment');
     *
     * // Check if currently injured
     * $isInjured = $repository->wrestlerHasActivePeriod($wrestler, 'currentInjury');
     *
     * // Check if currently suspended
     * $isSuspended = $repository->wrestlerHasActivePeriod($wrestler, 'currentSuspension');
     * ```
     */
    public function wrestlerHasActivePeriod(
        Wrestler $wrestler,
        string $currentRelationship
    ): bool {
        return $this->hasActivePeriod(
            model: $wrestler,
            currentRelationship: $currentRelationship
        );
    }

    /**
     * Get the duration of a wrestler's current active period.
     *
     * Calculates how long the wrestler has been in their current period
     * (employed, injured, suspended, etc.) in days.
     *
     * @param  Wrestler  $wrestler  The wrestler to check
     * @param  string  $currentRelationship  The current period relationship name
     * @return int|null Duration in days, or null if no active period
     *
     * @example
     * ```php
     * // Get employment duration
     * $employmentDays = $repository->getWrestlerPeriodDuration($wrestler, 'currentEmployment');
     * if ($employmentDays) {
     *     echo "Employed for {$employmentDays} days";
     * }
     *
     * // Get injury duration
     * $injuryDays = $repository->getWrestlerPeriodDuration($wrestler, 'currentInjury');
     * if ($injuryDays) {
     *     echo "Injured for {$injuryDays} days";
     * }
     * ```
     */
    public function getWrestlerPeriodDuration(
        Wrestler $wrestler,
        string $currentRelationship
    ): ?int {
        return $this->getCurrentPeriodDuration(
            model: $wrestler,
            currentRelationship: $currentRelationship
        );
    }

    /**
     * Get wrestlers with periods that started within a date range.
     *
     * Finds wrestlers who started a specific type of period (employment,
     * injury, etc.) within the given date range.
     *
     * @param  string  $relationship  The relationship name to query
     * @param  Carbon  $startDate  Range start date
     * @param  Carbon  $endDate  Range end date
     * @return Collection<int, Wrestler> Collection of wrestlers with periods in range
     *
     * @example
     * ```php
     * // Get wrestlers hired in the last month
     * $newHires = $repository->getWrestlersWithPeriodsInRange(
     *     relationship: 'employments',
     *     startDate: Carbon::now()->subMonth(),
     *     endDate: Carbon::now()
     * );
     *
     * // Get wrestlers injured this year
     * $injuredThisYear = $repository->getWrestlersWithPeriodsInRange(
     *     relationship: 'injuries',
     *     startDate: Carbon::now()->startOfYear(),
     *     endDate: Carbon::now()
     * );
     * ```
     */
    public function getWrestlersWithPeriodsInRange(
        string $relationship,
        Carbon $startDate,
        Carbon $endDate
    ): Collection {
        /** @var Collection<int, Wrestler> $wrestlers */
        $wrestlers = Wrestler::whereHas($relationship, function (Builder $query) use ($startDate, $endDate) {
            $query->where('started_at', '>=', $startDate)
                ->where('started_at', '<=', $endDate);
        })->get();

        return $wrestlers;
    }

    /**
     * Get wrestlers available for a new tag team.
     *
     * @return Collection<int, covariant Wrestler>
     */
    public function getAvailableForNewTagTeam(): Collection
    {
        return $this->getBaseAvailableWrestlers()->get();
    }

    /**
     * Get wrestlers available for an existing tag team.
     *
     * @return Collection<int, covariant Wrestler>
     */
    public function getAvailableForExistingTagTeam(TagTeam $tagTeam): Collection
    {
        return $this->getBaseAvailableWrestlers()
            ->orWhere(function (WrestlerBuilder $query) use ($tagTeam): void {
                $query->whereHas('currentTagTeam', function (Builder $query) use ($tagTeam): void {
                    $query->where('tag_team_id', $tagTeam->id);
                });
            })
            ->get();
    }

    /**
     * Get wrestlers available for booking (employed, bookable, not in tag team).
     *
     * @return Collection<int, covariant Wrestler>
     */
    public function getAvailableForBooking(): Collection
    {
        return Wrestler::query()
            ->where(function (WrestlerBuilder $query): void {
                $query->employed()
                    ->whereDoesntHave('currentTagTeam');
            })
            ->get();
    }

    /**
     * Get unemployed wrestlers.
     *
     * @return Collection<int, covariant Wrestler>
     */
    public function getUnemployed(): Collection
    {
        return Wrestler::query()
            ->where(function (WrestlerBuilder $query): void {
                $query->unemployed();
            })
            ->get();
    }

    /**
     * Get future employed wrestlers.
     *
     * @return Collection<int, covariant Wrestler>
     */
    public function getFutureEmployed(): Collection
    {
        return Wrestler::query()
            ->where(function (WrestlerBuilder $query): void {
                $query->futureEmployed();
            })
            ->get();
    }

    /**
     * Get wrestlers in a specific tag team.
     *
     * @return Collection<int, covariant Wrestler>
     */
    public function getInTagTeam(TagTeam $tagTeam): Collection
    {
        return Wrestler::query()
            ->whereHas('currentTagTeam', function (Builder $query) use ($tagTeam): void {
                $query->where('tag_team_id', $tagTeam->id);
            })
            ->get();
    }

    /**
     * Base query for wrestlers available for tag teams (unemployed, future employed, or bookable without current tag team).
     *
     * @return WrestlerBuilder<Wrestler>
     */
    private function getBaseAvailableWrestlers(): WrestlerBuilder
    {
        return Wrestler::query()
            ->where(function (WrestlerBuilder $query): void {
                $query->unemployed();
            })
            ->orWhere(function (WrestlerBuilder $query): void {
                $query->futureEmployed();
            })
            ->orWhere(function (WrestlerBuilder $query): void {
                $query->employed()
                    ->where('status', EmploymentStatus::Employed)
                    ->whereDoesntHave('currentTagTeam');
            });
    }

    /**
     * Add a manager to the wrestler.
     */
    public function addManager(Wrestler $wrestler, Manager $manager, Carbon $startDate): void
    {
        $wrestler->managers()->attach($manager->id, [
            'started_at' => $startDate,
            'ended_at' => null,
        ]);
    }

    /**
     * Remove a manager from the wrestler.
     */
    public function removeManager(Wrestler $wrestler, Manager $manager, Carbon $endDate): void
    {
        $wrestler->managers()
            ->wherePivot('ended_at', null)
            ->updateExistingPivot($manager->id, [
                'ended_at' => $endDate,
            ]);
    }

    /**
     * Restore a soft-deleted wrestler.
     */
    public function restore(Wrestler $wrestler): void
    {
        $wrestler->restore();
    }
}

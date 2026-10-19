<?php

declare(strict_types=1);

namespace App\Actions\Managers;

use App\Exceptions\Roster\Managers\CannotBeAssignedException;
use App\Lifecycle\Roster\Managers\ManagerAssignmentEligibility;
use App\Models\Contracts\Manageable;
use App\Models\Roster\Managers\Manager;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;

class AssignManagersAction
{
    public function __construct(protected ManagerAssignmentEligibility $eligibility) {}

    /**
     * Attach newly assigned managers, each of whom must be employed and not retired, suspended or injured.
     *
     * Runs inside the caller's transaction, after the wrestler or tag team row is locked (or inserted). The
     * managers are then locked in ascending id order and checked, so a release, retirement, suspension or injury
     * committing meanwhile cannot slip past the check (lock order: owner, then managers). Only the managers
     * passed in are checked: callers that synchronize pass just the newly added ones, so a manager who was
     * assigned earlier and has since become unavailable stays.
     *
     * @param  Manageable<*, *>  $manageable
     * @param  Collection<int, Manager>|null  $managers
     *
     * @throws CannotBeAssignedException When a manager is not employed, or is retired, suspended or injured
     */
    public function handle(Manageable $manageable, ?Collection $managers, Carbon $date): void
    {
        if (! $managers instanceof Collection || $managers->isEmpty()) {
            return;
        }

        $lockedManagers = Manager::query()
            ->withTrashed()
            ->whereKey($managers->modelKeys())
            ->inLockOrder()
            ->lockForUpdate()
            ->get();

        foreach ($lockedManagers as $manager) {
            $this->eligibility->ensureCanBeAssigned($manager);
        }

        $manageable->managers()->attach($managers->modelKeys(), [
            'hired_at' => $date,
            'fired_at' => null,
        ]);
    }
}

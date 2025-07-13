<?php

declare(strict_types=1);

namespace App\Actions\Managers;

use App\Exceptions\Status\CannotBeInjuredException;
use App\Models\Managers\Manager;
use App\Repositories\ManagerRepository;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Lorisleiva\Actions\Concerns\AsAction;

class InjureAction extends BaseManagerAction
{
    use AsAction;

    public function __construct(
        ManagerRepository $managerRepository
    ) {
        parent::__construct($managerRepository);
    }

    /**
     * Record a manager injury.
     *
     * This handles the complete manager injury workflow:
     * - Validates the manager can be injured (currently employed, not already injured)
     * - Creates an injury record with the specified start date
     * - Temporarily removes the manager from active wrestler/tag team management duties
     * - Maintains employment status while marking as unavailable due to injury
     *
     * @param  Manager  $manager  The manager to mark as injured
     * @param  Carbon|null  $injureDate  The injury date (defaults to now)
     *
     * @throws CannotBeInjuredException When manager cannot be injured due to business rules
     *
     * @example
     * ```php
     * // Mark manager as injured immediately
     * InjureAction::run($manager);
     *
     * // Record injury with specific date
     * InjureAction::run($manager, Carbon::parse('2024-01-15'));
     * ```
     */
    public function handle(Manager $manager, ?Carbon $injureDate = null): void
    {
        $manager->ensureCanBeInjured();

        $injureDate = $this->getEffectiveDate($injureDate);

        DB::transaction(function () use ($manager, $injureDate): void {
            $this->managerRepository->createInjury($manager, $injureDate);
        });
    }
}

<?php

declare(strict_types=1);

namespace App\Actions\Managers;

use App\Exceptions\Status\CannotBeReinstatedException;
use App\Models\Managers\Manager;
use App\Repositories\ManagerRepository;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Lorisleiva\Actions\Concerns\AsAction;

final class ReinstateAction extends BaseManagerAction
{
    use AsAction;

    public function __construct(
        ManagerRepository $managerRepository
    ) {
        parent::__construct($managerRepository);
    }

    /**
     * Reinstate a suspended manager.
     *
     * This handles the complete manager reinstatement workflow:
     * - Validates the manager can be reinstated (currently suspended)
     * - Ends the current suspension period with the specified date
     * - Restores the manager to active management duties
     * - Makes the manager available for wrestler/tag team assignments
     *
     * @param  Manager  $manager  The manager to reinstate
     * @param  Carbon|null  $reinstatementDate  The reinstatement date (defaults to now)
     *
     * @throws CannotBeReinstatedException When manager cannot be reinstated due to business rules
     *
     * @example
     * ```php
     * // Reinstate manager immediately
     * ReinstateAction::run($manager);
     *
     * // Reinstate with specific date
     * ReinstateAction::run($manager, Carbon::parse('2024-01-01'));
     * ```
     */
    public function handle(Manager $manager, ?Carbon $reinstatementDate = null): void
    {
        $manager->ensureCanBeReinstated();

        $reinstatementDate = $this->getEffectiveDate($reinstatementDate);

        DB::transaction(function () use ($manager, $reinstatementDate): void {
            $this->managerRepository->endSuspension($manager, $reinstatementDate);
        });
    }
}

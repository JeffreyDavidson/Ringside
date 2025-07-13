<?php

declare(strict_types=1);

namespace App\Actions\Managers;

use App\Exceptions\Status\CannotBeClearedFromInjuryException;
use App\Models\Managers\Manager;
use App\Repositories\ManagerRepository;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Lorisleiva\Actions\Concerns\AsAction;

class HealAction extends BaseManagerAction
{
    use AsAction;

    public function __construct(
        ManagerRepository $managerRepository
    ) {
        parent::__construct($managerRepository);
    }

    /**
     * Heal a manager from injury and return them to active management.
     *
     * This handles the complete injury recovery workflow:
     * - Validates the manager can be healed from injury (currently injured)
     * - Ends the current injury period with the specified recovery date
     * - Restores the manager to active talent management duties
     * - Makes the manager available for wrestler and tag team assignments again
     * - Preserves injury history for medical and administrative records
     *
     * @param  Manager  $manager  The injured manager to heal
     * @param  Carbon|null  $recoveryDate  The recovery date (defaults to now)
     *
     * @throws CannotBeClearedFromInjuryException When manager cannot be healed due to business rules
     *
     * @example
     * ```php
     * // Heal injury immediately
     * HealAction::run($manager);
     *
     * // Heal injury with specific recovery date
     * HealAction::run($manager, Carbon::parse('2024-02-01'));
     * ```
     */
    public function handle(Manager $manager, ?Carbon $recoveryDate = null): void
    {
        $manager->ensureCanBeHealed();

        $recoveryDate = $this->getEffectiveDate($recoveryDate);

        DB::transaction(function () use ($manager, $recoveryDate): void {
            $this->managerRepository->endInjury($manager, $recoveryDate);
        });
    }
}

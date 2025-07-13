<?php

declare(strict_types=1);

namespace App\Actions\Managers;

use App\Actions\Concerns\StatusTransitionPipeline;
use App\Models\Managers\Manager;
use App\Repositories\ManagerRepository;
use Exception;
use Illuminate\Support\Carbon;
use Lorisleiva\Actions\Concerns\AsAction;

class EmployAction extends BaseManagerAction
{
    use AsAction;

    public function __construct(
        ManagerRepository $managerRepository
    ) {
        parent::__construct($managerRepository);
    }

    /**
     * Employ a manager.
     *
     * This handles the complete manager employment workflow using the StatusTransitionPipeline:
     * - Validates the manager can be employed (not retired, not already employed)
     * - Ends retirement if currently retired
     * - Creates an employment record with the specified start date
     * - Makes the manager available for talent management assignments
     *
     * @param  Manager  $manager  The manager to employ
     * @param  Carbon|null  $startDate  The employment start date (defaults to now)
     *
     * @throws Exception When manager cannot be employed due to business rules
     *
     * @example
     * ```php
     * // Employ manager immediately
     * EmployAction::run($manager);
     *
     * // Employ with specific start date
     * EmployAction::run($manager, Carbon::parse('2024-01-01'));
     * ```
     */
    public function handle(Manager $manager, ?Carbon $startDate = null): void
    {
        StatusTransitionPipeline::employ($manager, $startDate)->execute();
    }
}

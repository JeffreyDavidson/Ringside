<?php

declare(strict_types=1);

namespace App\Actions\Wrestlers;

use App\Data\Wrestlers\WrestlerData;
use App\Models\Wrestlers\Wrestler;
use App\Services\WrestlerManagerAssignmentService;
use App\Support\DateHelper;
use Illuminate\Support\Facades\DB;
use Lorisleiva\Actions\Concerns\AsAction;

class CreateAction
{
    use AsAction;

    /**
     * Create a new wrestler create action instance.
     */
    public function __construct(
        protected EmployAction $employAction,
        protected WrestlerManagerAssignmentService $managerAssignmentService
    ) {}

    /**
     * Create a new wrestler and establish their career.
     *
     * This handles the complete wrestler creation workflow:
     * - Creates the wrestler record with personal and professional details
     * - Uses EmployAction for consistent employment handling if employment_date provided
     * - Uses WrestlerManagerAssignmentService for consistent manager assignment
     * - Establishes the wrestler as available for match bookings and storylines
     * - Handles all relationship dependencies and employment cascades
     *
     * ARCHITECTURAL PATTERN:
     * Uses dedicated services (EmployAction, WrestlerManagerAssignmentService) for consistent
     * handling instead of manual database operations. This ensures proper StatusTransitionPipeline
     * usage and cascade behavior.
     *
     * @param  WrestlerData  $wrestlerData  The data transfer object containing wrestler information
     * @return Wrestler The newly created wrestler instance
     *
     * @example
     * ```php
     * $wrestlerData = new WrestlerData([
     *     'name' => 'John Doe',
     *     'hometown' => 'Chicago, IL',
     *     'height' => 72,
     *     'weight' => 220,
     *     'signature_moves' => ['Suplex', 'DDT'],
     *     'employment_date' => now(),
     *     'managers' => [1, 2] // Manager IDs
     * ]);
     * $wrestler = CreateAction::run($wrestlerData);
     * ```
     */
    public function handle(WrestlerData $wrestlerData): Wrestler
    {
        return DB::transaction(function () use ($wrestlerData): Wrestler {
            $wrestler = Wrestler::query()->create([
                'name' => $wrestlerData->name,
                'height' => $wrestlerData->height,
                'weight' => $wrestlerData->weight,
                'hometown' => $wrestlerData->hometown,
                'signature_move' => $wrestlerData->signature_move,
            ]);

            // Handle manager assignment using dedicated service
            if ($wrestlerData->hasManagers()) {
                $datetime = DateHelper::resolveDate($wrestlerData->employment_date);

                $this->managerAssignmentService->assignManagersToWrestler(
                    $wrestler,
                    $wrestlerData->managers,
                    $datetime
                );
            }

            // Handle wrestler employment using EmployAction for consistency
            if (isset($wrestlerData->employment_date)) {
                $this->employAction->handle($wrestler, $wrestlerData->employment_date);
            }

            return $wrestler;
        });
    }
}

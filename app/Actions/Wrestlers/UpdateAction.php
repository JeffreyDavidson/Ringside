<?php

declare(strict_types=1);

namespace App\Actions\Wrestlers;

use App\Data\Wrestlers\WrestlerData;
use App\Models\Wrestlers\Wrestler;
use App\Support\DateHelper;
use Illuminate\Support\Facades\DB;
use Lorisleiva\Actions\Concerns\AsAction;

/**
 * Action for updating wrestler information and managing employment status.
 *
 * This action handles the complete workflow for updating a wrestler's information,
 * including automatically creating employment records when appropriate. It ensures
 * data consistency by performing updates and employment operations atomically.
 *
 * The action follows these business rules:
 * - Always updates the wrestler's basic information first
 * - Uses EmployAction for consistent employment handling when employment_date is provided
 * - Automatically employs managers through EmployAction cascade strategies
 * - Uses DateHelper for consistent date handling
 * - Maintains employment history through proper action coordination
 */
class UpdateAction
{
    use AsAction;

    /**
     * Create a new update action instance.
     */
    public function __construct(
        protected EmployAction $employAction
    ) {}

    /**
     * Update a wrestler's information and handle employment status.
     *
     * This handles the complete update workflow:
     * - Updates wrestler's basic information using DateHelper for consistent date handling
     * - Uses EmployAction for consistent employment creation when employment_date provided
     * - Automatically employs managers through EmployAction cascade strategies
     * - Maintains transaction boundaries for data consistency
     */
    public function handle(Wrestler $wrestler, WrestlerData $wrestlerData): Wrestler
    {
        return DB::transaction(function () use ($wrestler, $wrestlerData): Wrestler {
            // Update the wrestler's basic information
            $wrestler->update([
                'name' => $wrestlerData->name,
                'height' => $wrestlerData->height,
                'weight' => $wrestlerData->weight,
                'hometown' => $wrestlerData->hometown,
                'signature_move' => $wrestlerData->signature_move,
            ]);

            // Employ wrestler if employment_date is provided and they're not already employed
            if (! is_null($wrestlerData->employment_date) && ! $wrestler->isEmployed()) {
                $employmentDate = DateHelper::resolveDate($wrestlerData->employment_date);
                $this->employAction->handle($wrestler, $employmentDate);
            }

            return $wrestler;
        });
    }
}

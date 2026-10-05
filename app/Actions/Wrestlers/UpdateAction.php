<?php

declare(strict_types=1);

namespace App\Actions\Wrestlers;

use App\Data\Wrestlers\WrestlerData;
use App\Models\Roster\Wrestlers\Wrestler;
use Illuminate\Support\Facades\DB;

/**
 * Action for updating wrestler information and managing employment status.
 *
 * This action handles the complete workflow for updating a wrestler's information,
 * including automatically creating employment records when appropriate. It ensures
 * data consistency by performing updates and employment operations atomically.
 *
 * The action follows these business rules:
 * - Always updates the wrestler's basic information first
 * - Uses EmployAction for consistent employment handling when employment_date is provided and the wrestler has no employment history
 * - Automatically employs managers through EmployAction's typed collaborator
 * - Maintains employment history through proper action coordination
 */
class UpdateAction
{
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
     * - Updates wrestler's basic information
     * - Uses EmployAction for consistent employment creation when employment_date provided and the wrestler has never been employed
     * - Automatically employs managers through EmployAction's typed collaborator
     * - Maintains transaction boundaries for data consistency
     */
    public function handle(Wrestler $wrestler, WrestlerData $wrestlerData): Wrestler
    {
        return DB::transaction(function () use ($wrestler, $wrestlerData): Wrestler {
            $lockedWrestler = $wrestler->refreshForUpdate();

            $lockedWrestler->update([
                'name' => $wrestlerData->name,
                'height' => $wrestlerData->height,
                'weight' => $wrestlerData->weight,
                'hometown' => $wrestlerData->hometown,
                'signature_move' => $wrestlerData->signature_move,
            ]);

            // Only a wrestler with no employment history is employed from the form date; a released, retired or
            // future-employed wrestler keeps their history (employing again would overlap or be rejected).
            if (! is_null($wrestlerData->employment_date) && ! $lockedWrestler->employments()->exists()) {
                $this->employAction->handle($lockedWrestler, $wrestlerData->employment_date);
            }

            return $lockedWrestler;
        });
    }
}

<?php

declare(strict_types=1);

namespace App\Actions\Wrestlers;

use App\Actions\Managers\EmployAction as ManagersEmployAction;
use App\Data\Wrestlers\WrestlerData;
use App\Enums\Shared\EmploymentStatus;
use App\Models\Wrestlers\Wrestler;
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
 * - Creates employment only if an employment_date is provided and the wrestler is not currently employed
 * - Uses direct Eloquent operations for data persistence
 * - Maintains employment history through employment relationships
 */
class UpdateAction
{
    use AsAction;

    /**
     * Create a new update action instance.
     */
    public function __construct(
        protected ManagersEmployAction $managersEmployAction
    ) {}

    /**
     * Update a wrestler's information and handle employment status.
     *
     * This handles the complete update workflow:
     * - Updates wrestler's basic information
     * - Creates employment if employment_date provided and eligible
     * - Employs any current managers who are not yet employed
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

            // Track if wrestler was just employed
            $wasEmployed = $wrestler->isEmployed();

            // Create employment record if employment_date is provided and wrestler is eligible
            if (! is_null($wrestlerData->employment_date) && ! $wrestler->isEmployed()) {
                $wrestler->employments()->create([
                    'started_at' => $wrestlerData->employment_date,
                    'ended_at' => null,
                    'status' => EmploymentStatus::Employed,
                ]);
            }

            // If wrestler just got employed, employ their managers too
            if (! $wasEmployed && $wrestler->isEmployed() && $wrestlerData->employment_date) {
                $wrestler->currentManagers
                    ->filter(fn ($manager) => ! $manager->isEmployed())
                    ->each(fn ($manager) => $this->managersEmployAction->handle($manager, $wrestlerData->employment_date));
            }

            return $wrestler;
        });
    }
}

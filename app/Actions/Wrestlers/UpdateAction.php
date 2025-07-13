<?php

declare(strict_types=1);

namespace App\Actions\Wrestlers;

use App\Actions\Managers\EmployAction as ManagersEmployAction;
use App\Data\Wrestlers\WrestlerData;
use App\Models\Wrestlers\Wrestler;
use App\Repositories\Concerns\ManagesEmployment;
use App\Repositories\WrestlerRepository;
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
 * - Uses the repository pattern for data persistence
 * - Maintains employment history through the ManagesEmployment trait
 *
 * @see ManagesEmployment For employment management operations
 * @see Wrestler For the wrestler model
 * @see WrestlerData For the data transfer object structure
 */
class UpdateAction extends BaseWrestlerAction
{
    use AsAction;

    /**
     * Create a new update action instance.
     */
    public function __construct(
        WrestlerRepository $wrestlerRepository,
        protected ManagersEmployAction $managersEmployAction
    ) {
        parent::__construct($wrestlerRepository);
    }

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
            $this->wrestlerRepository->update($wrestler, $wrestlerData);

            // Track if wrestler was just employed
            $wasEmployed = $wrestler->isEmployed();

            // Create employment record if employment_date is provided and wrestler is eligible
            if (! is_null($wrestlerData->employment_date) && ! $wrestler->isEmployed()) {
                $this->wrestlerRepository->createEmployment($wrestler, $wrestlerData->employment_date);
            }

            // If wrestler just got employed, employ their managers too
            if (! $wasEmployed && $wrestler->isEmployed() && $wrestlerData->employment_date) {
                $this->employCurrentManagers($wrestler, $wrestlerData->employment_date, $this->managersEmployAction);
            }

            return $wrestler;
        });
    }
}

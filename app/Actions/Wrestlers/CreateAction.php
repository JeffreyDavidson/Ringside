<?php

declare(strict_types=1);

namespace App\Actions\Wrestlers;

use App\Actions\Managers\EmployAction as ManagersEmployAction;
use App\Data\Wrestlers\WrestlerData;
use App\Models\Wrestlers\Wrestler;
use App\Repositories\WrestlerRepository;
use Illuminate\Support\Facades\DB;
use Lorisleiva\Actions\Concerns\AsAction;

class CreateAction extends BaseWrestlerAction
{
    use AsAction;

    /**
     * Create a new wrestler create action instance.
     */
    public function __construct(
        WrestlerRepository $wrestlerRepository,
        protected ManagersEmployAction $managersEmployAction
    ) {
        parent::__construct($wrestlerRepository);
    }

    /**
     * Create a new wrestler and establish their career.
     *
     * This handles the complete wrestler creation workflow:
     * - Creates the wrestler record with personal and professional details
     * - Creates employment record if employment_date is provided
     * - Assigns managers if provided and ensures they are employed
     * - Establishes the wrestler as available for match bookings and storylines
     * - Handles all relationship dependencies and employment cascades
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
            $wrestler = $this->wrestlerRepository->create($wrestlerData);

            // Handle wrestler employment
            if (isset($wrestlerData->employment_date)) {
                $this->wrestlerRepository->createEmployment($wrestler, $wrestlerData->employment_date);
            }

            // Handle manager assignment and employment
            if (isset($wrestlerData->managers) && ! empty($wrestlerData->managers)) {
                $datetime = $this->getEffectiveDate($wrestlerData->employment_date);

                // Assign managers to wrestler and employ them if needed
                foreach ($wrestlerData->managers as $manager) {
                    $this->wrestlerRepository->addManager($wrestler, $manager, $datetime);

                    if (! $manager->isEmployed()) {
                        $this->managersEmployAction->handle($manager, $datetime);
                    }
                }
            }

            return $wrestler;
        });
    }
}

<?php

declare(strict_types=1);

namespace App\Actions\Managers;

use App\Lifecycle\Roster\Individuals\IndividualEmploymentEligibility;
use App\Models\Contracts\Manageable;
use App\Models\Roster\Managers\Manager;
use Illuminate\Support\Carbon;

class EmployCurrentManagersAction
{
    public function __construct(
        private readonly EmployAction $employManager,
        private readonly IndividualEmploymentEligibility $eligibility,
    ) {}

    /**
     * @param  Manageable<*, *>  $manageable
     */
    public function handle(Manageable $manageable, Carbon $employmentDate): void
    {
        $managers = $manageable->currentManagers()
            ->inLockOrder()
            ->get()
            ->filter(fn (Manager $manager): bool => $this->eligibility->canEmploy($manager));

        foreach ($managers as $manager) {
            $this->employManager->handle($manager, $employmentDate);
        }
    }
}

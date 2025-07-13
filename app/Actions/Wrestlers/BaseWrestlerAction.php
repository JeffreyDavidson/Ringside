<?php

declare(strict_types=1);

namespace App\Actions\Wrestlers;

use App\Actions\Concerns\ManagesDates;
use App\Actions\Managers\EmployAction as ManagersEmployAction;
use App\Models\Wrestlers\Wrestler;
use App\Repositories\WrestlerRepository;
use Illuminate\Support\Carbon;

/**
 * Base class for all wrestler actions.
 *
 * Provides common functionality for actions that operate on wrestlers:
 * - Centralized repository access through dependency injection
 * - Status transition management capabilities
 * - Foundation for wrestler-related business operations
 * - Standardized repository access patterns
 * - Business logic validation methods
 */
abstract class BaseWrestlerAction
{
    use ManagesDates;

    /**
     * Create a new base wrestler action instance.
     */
    public function __construct(protected WrestlerRepository $wrestlerRepository) {}

    /**
     * Employ all current managers who are not yet employed.
     *
     * This is commonly needed when a wrestler becomes employed, as their
     * managers should also be employed to manage them effectively.
     */
    protected function employCurrentManagers(Wrestler $wrestler, Carbon $employmentDate, ManagersEmployAction $managersEmployAction): void
    {
        $wrestler->currentManagers
            ->filter(fn ($manager) => ! $manager->isEmployed())
            ->each(fn ($manager) => $managersEmployAction->handle($manager, $employmentDate));
    }

    /**
     * Remove wrestler from all current manager relationships.
     *
     * Always calls the repository method to ensure proper cleanup,
     * even if the wrestler has no current managers.
     */
    protected function removeCurrentManagers(Wrestler $wrestler, Carbon $removalDate): void
    {
        $this->wrestlerRepository->removeFromCurrentManagers($wrestler, $removalDate);
    }
}

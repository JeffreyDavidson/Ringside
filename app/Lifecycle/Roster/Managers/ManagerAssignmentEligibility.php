<?php

declare(strict_types=1);

namespace App\Lifecycle\Roster\Managers;

use App\Exceptions\Roster\Managers\CannotBeAssignedException;
use App\Lifecycle\Concerns\AnswersEligibility;
use App\Models\Roster\Managers\Manager;

/**
 * Decides whether a manager may be newly attached to a wrestler or tag team: employed now, and not retired,
 * suspended or injured. Managers who are already attached are never checked here, so a later change of
 * state does not drop them on the next save.
 */
final class ManagerAssignmentEligibility
{
    use AnswersEligibility;

    public function canBeAssigned(Manager $manager): bool
    {
        return $this->passes(fn () => $this->ensureCanBeAssigned($manager), CannotBeAssignedException::class);
    }

    /** @throws CannotBeAssignedException */
    public function ensureCanBeAssigned(Manager $manager): void
    {
        if ($manager->trashed()) {
            throw CannotBeAssignedException::deleted($manager);
        }

        if ($manager->hasCurrentRetirement()) {
            throw CannotBeAssignedException::retired($manager);
        }

        if (! $manager->hasCurrentEmployment()) {
            throw CannotBeAssignedException::unemployed($manager);
        }

        if ($manager->isSuspended()) {
            throw CannotBeAssignedException::suspended($manager);
        }

        if ($manager->isInjured()) {
            throw CannotBeAssignedException::injured($manager);
        }
    }
}

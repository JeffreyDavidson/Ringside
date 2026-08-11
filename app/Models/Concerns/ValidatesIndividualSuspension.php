<?php

declare(strict_types=1);

namespace App\Models\Concerns;

use App\Enums\Shared\EmploymentStatus;
use App\Exceptions\Roster\CannotBeReinstatedException;
use App\Exceptions\Roster\CannotBeSuspendedException;
use App\Models\Contracts\Bookable;
use App\Models\Managers\Manager;
use App\Models\Referees\Referee;
use App\Models\Wrestlers\Wrestler;
use LogicException;

trait ValidatesIndividualSuspension
{
    public function canBeSuspended(): bool
    {
        try {
            $this->ensureCanBeSuspended();

            return true;
        } catch (CannotBeSuspendedException) {
            return false;
        }
    }

    /** @throws CannotBeSuspendedException */
    public function ensureCanBeSuspended(): void
    {
        $entity = $this->individualSuspensionEntity();

        if ($entity->hasStatus(EmploymentStatus::Unemployed)) {
            throw CannotBeSuspendedException::unemployed($entity);
        }

        if ($entity->isReleased()) {
            throw CannotBeSuspendedException::released($entity);
        }

        if ($entity->isRetired()) {
            throw CannotBeSuspendedException::retired($entity);
        }

        if ($entity->hasFutureEmployment()) {
            throw CannotBeSuspendedException::hasFutureEmployment($entity);
        }

        if ($entity->isInjured()) {
            throw CannotBeSuspendedException::injured($entity);
        }

        if ($entity->isSuspended()) {
            throw CannotBeSuspendedException::suspended($entity);
        }
    }

    public function canBeReinstated(): bool
    {
        try {
            $this->ensureCanBeReinstated();

            return true;
        } catch (CannotBeReinstatedException) {
            return false;
        }
    }

    /** @throws CannotBeReinstatedException */
    public function ensureCanBeReinstated(): void
    {
        $entity = $this->individualSuspensionEntity();

        if ($entity->isInjured()) {
            throw CannotBeReinstatedException::injured($entity);
        }

        if (! $entity->isSuspended()) {
            throw CannotBeReinstatedException::available($entity);
        }

        if ($entity->isNotInEmployment()) {
            throw CannotBeReinstatedException::unemployed($entity);
        }

        if ($entity->hasFutureEmployment()) {
            throw CannotBeReinstatedException::hasFutureEmployment($entity);
        }

        if ($entity->isRetired()) {
            throw CannotBeReinstatedException::retired($entity);
        }

        if ($entity instanceof Bookable && $entity->isBookable()) {
            throw CannotBeReinstatedException::bookable($entity);
        }
    }

    private function individualSuspensionEntity(): Wrestler|Manager|Referee
    {
        if ($this instanceof Wrestler || $this instanceof Manager || $this instanceof Referee) {
            return $this;
        }

        throw new LogicException(sprintf('%s does not support individual suspension validation.', static::class));
    }
}

<?php

declare(strict_types=1);

namespace App\Models\Concerns;

use App\Exceptions\Roster\CannotBeUnretiredException as RosterCannotBeUnretiredException;
use App\Exceptions\Titles\CannotBeUnretiredException as TitlesCannotBeUnretiredException;
use App\Models\Managers\Manager;
use App\Models\Referees\Referee;
use App\Models\Titles\Title;
use App\Models\Validation\Strategies\IndividualRetirementValidation;
use App\Models\Validation\Strategies\TitleRetirementValidation;
use App\Models\Wrestlers\Wrestler;
use Exception;
use LogicException;

/**
 * Provides shared retirement validation for wrestlers, managers, referees, and titles.
 *
 * @see IsRetirable For core retirement functionality
 */
trait ValidatesRetirement
{
    public function canBeRetired(): bool
    {
        try {
            $this->ensureCanBeRetired();

            return true;
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Ensure the model can be retired.
     *
     * @throws CannotBeRetiredException When retirement is not allowed
     */
    public function ensureCanBeRetired(): void
    {
        if ($this instanceof Title) {
            (new TitleRetirementValidation())->validate($this);

            return;
        }

        if ($this instanceof Wrestler || $this instanceof Manager || $this instanceof Referee) {
            (new IndividualRetirementValidation())->validate($this);

            return;
        }

        throw new LogicException(sprintf('%s does not support shared retirement validation.', static::class));
    }

    public function canBeUnretired(): bool
    {
        return $this->isRetired();
    }

    /**
     * Ensure the model can be unretired.
     *
     * @throws CannotBeUnretiredException When unretirement is not allowed
     */
    public function ensureCanBeUnretired(): void
    {
        if ($this->trashed()) {
            throw new Exception('Cannot unretire a deleted record.');
        }

        if (! $this->isRetired()) {
            if ($this instanceof Title) {
                throw TitlesCannotBeUnretiredException::notRetired($this);
            }

            throw RosterCannotBeUnretiredException::notRetired($this);
        }
    }
}

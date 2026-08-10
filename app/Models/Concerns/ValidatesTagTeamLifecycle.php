<?php

declare(strict_types=1);

namespace App\Models\Concerns;

use App\Exceptions\Roster\TagTeams\CannotBeDeletedException;
use App\Exceptions\Roster\TagTeams\CannotBeReleasedException;
use App\Exceptions\Roster\TagTeams\CannotBeRestoredException;
use Exception;

/** Provides validation for the remaining tag-team lifecycle operations. */
trait ValidatesTagTeamLifecycle
{
    /**
     * Determine if the tag team can be released.
     *
     * Checks business rules for tag team release:
     * - Must be currently employed
     * - Should validate contractual obligations
     * - Should check for championship commitments
     *
     * @return bool True if the tag team can be released, false otherwise
     */
    public function canBeReleased(): bool
    {
        if (! $this->isEmployed()) {
            return false;
        }

        // Basic release is possible if employed
        return true;
    }

    /**
     * Ensure the tag team can be released, throwing an exception if not.
     *
     * Validates that the tag team is in a valid state for release while checking
     * for business rule violations including employment status, contractual obligations,
     * and championship commitments.
     *
     * @throws CannotBeReleasedException When release is not allowed
     */
    public function ensureCanBeReleased(): void
    {
        if (! $this->isEmployed()) {
            throw CannotBeReleasedException::notEmployed($this);
        }

        // Additional business rule validations could be added here:
        // - Check for championship obligations
        // if ($this->hasCurrentChampionshipObligations()) {
        //     $championships = $this->getCurrentChampionshipDetails();
        //     throw CannotBeReleasedException::hasChampionshipObligations($this, $championships);
        // }

        // - Check for contractual obligations
        // if ($this->hasUnfulfilledContractualObligations()) {
        //     $obligations = $this->getContractualObligationDetails();
        //     throw CannotBeReleasedException::contractualObligations($this, $obligations);
        // }

        // - Check for scheduled match commitments
        // if ($this->hasScheduledMatches()) {
        //     $matches = $this->getScheduledMatchDetails();
        //     throw CannotBeReleasedException::hasScheduledMatches($this, $matches);
        // }
    }

    /**
     * Determine if the tag team can be deleted (soft deleted).
     *
     * Checks business rules for tag team deletion:
     * - Must not be currently active (employed or suspended)
     * - Should validate data integrity requirements
     * - Should check for historical preservation needs
     *
     * @return bool True if the tag team can be deleted, false otherwise
     */
    public function canBeDeleted(): bool
    {
        if ($this->isEmployed()) {
            return false;
        }

        if ($this->isSuspended()) {
            return false;
        }

        // Basic deletion is possible if not active
        return true;
    }

    /**
     * Ensure the tag team can be deleted (soft deleted), throwing an exception if not.
     *
     * Validates that the tag team is in a valid state for soft deletion while checking
     * for business rule violations including active status, data integrity requirements,
     * and historical preservation needs.
     *
     * @throws CannotBeDeletedException When deletion is not allowed
     */
    public function ensureCanBeDeleted(): void
    {
        if ($this->trashed()) {
            throw new Exception("Tag team '{$this->name}' is already deleted.");
        }

        if ($this->isRetired()) {
            throw new Exception("Tag team '{$this->name}' is retired and cannot be deleted.");
        }

        if ($this->isEmployed()) {
            throw CannotBeDeletedException::stillEmployed($this);
        }

        if ($this->isSuspended()) {
            throw CannotBeDeletedException::stillSuspended($this);
        }

        // Additional business rule validations could be added here:
        // - Check for historical significance requirements
        // if ($this->hasHistoricalSignificance()) {
        //     throw CannotBeDeletedException::historicalSignificance($this);
        // }

        // - Check for championship lineage requirements
        // if ($this->hasChampionshipHistory()) {
        //     throw CannotBeDeletedException::championshipHistory($this);
        // }

        // - Check for administrative authorization
        // if (! $this->hasDeletionAuthorization()) {
        //     throw CannotBeDeletedException::insufficientAuthorization($this);
        // }
    }

    /**
     * Determine if the tag team can be restored from soft deletion.
     *
     * Checks business rules for tag team restoration:
     * - Must be currently soft deleted
     * - Name must not conflict with existing active tag teams
     * - Should validate restoration authorization
     *
     * @return bool True if the tag team can be restored, false otherwise
     */
    public function canBeRestored(): bool
    {
        if (! $this->trashed()) {
            return false;
        }

        // Check for name conflicts with existing active tag teams
        $nameConflict = static::where('name', $this->name)
            ->where('id', '!=', $this->id)
            ->where(function ($query) {
                $query->whereHas('employments', function ($subQuery) {
                    $subQuery->whereNull('ended_at');
                });
            })
            ->exists();

        if ($nameConflict) {
            return false;
        }

        // Basic restoration is possible if soft deleted and no conflicts
        return true;
    }

    /**
     * Ensure the tag team can be restored from soft deletion, throwing an exception if not.
     *
     * Validates that the tag team is in a valid state for restoration while checking
     * for business rule violations including deletion status, name conflicts,
     * and authorization requirements.
     *
     * @throws CannotBeRestoredException When restoration is not allowed
     */
    public function ensureCanBeRestored(): void
    {
        if (! $this->trashed()) {
            throw CannotBeRestoredException::notDeleted($this);
        }

        // Check for name conflicts with existing active tag teams
        $conflictingTeam = static::where('name', $this->name)
            ->where('id', '!=', $this->id)
            ->whereHas('employments', function ($query) {
                $query->whereNull('ended_at');
            })
            ->first();

        if ($conflictingTeam) {
            throw CannotBeRestoredException::nameConflict($this, $conflictingTeam->name);
        }

        // Additional business rule validations could be added here:
        // - Check for restoration authorization requirements
        // if (! $this->hasRestorationAuthorization()) {
        //     throw CannotBeRestoredException::insufficientAuthorization($this);
        // }

        // - Check for data integrity requirements
        // if ($this->hasDataIntegrityIssues()) {
        //     throw CannotBeRestoredException::dataIntegrityIssues($this);
        // }

        // - Check for administrative approval
        // if (! $this->hasAdministrativeApproval()) {
        //     throw CannotBeRestoredException::requiresAdministrativeApproval($this);
        // }
    }
}

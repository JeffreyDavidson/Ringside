<?php

declare(strict_types=1);

namespace App\Models\Concerns;

use App\Exceptions\Roster\TagTeams\CannotBeDeletedException;
use App\Exceptions\Roster\TagTeams\CannotBeEmployedException;
use App\Exceptions\Roster\TagTeams\CannotBeReinstatedException;
use App\Exceptions\Roster\TagTeams\CannotBeReleasedException;
use App\Exceptions\Roster\TagTeams\CannotBeRestoredException;
use App\Exceptions\Roster\TagTeams\CannotBeRetiredException;
use App\Exceptions\Roster\TagTeams\CannotBeSuspendedException;
use App\Exceptions\Roster\TagTeams\CannotBeUnretiredException;

/**
 * Provides tag team lifecycle validation functionality for TagTeam models.
 *
 * This trait adds comprehensive validation methods for all tag team lifecycle operations
 * including employment, retirement, unretirement, and other state transitions.
 * It ensures business rules are enforced consistently across all tag team actions.
 *
 * BUSINESS CONTEXT:
 * Tag teams have complex lifecycle management involving partnership dynamics,
 * individual member states, employment cascading, and storyline considerations.
 * This trait provides centralized validation to ensure all transitions are valid.
 *
 * VALIDATION AREAS:
 * - Employment validation (partner availability, standards compliance)
 * - Retirement validation (employment status, championship obligations)
 * - Unretirement validation (partner availability, name conflicts)
 * - State transition validation (preventing invalid state changes)
 *
 * @example
 * ```php
 * // Use validation methods in tag team actions
 * $tagTeam->ensureCanBeEmployed();     // For employment validation
 * $tagTeam->ensureCanBeRetired();      // For retirement validation
 * $tagTeam->ensureCanBeUnretired();    // For unretirement validation
 * $tagTeam->canBeEmployed();           // Returns boolean for employment
 * $tagTeam->canBeRetired();            // Returns boolean for retirement
 * $tagTeam->canBeUnretired();          // Returns boolean for unretirement
 * ```
 */
trait ValidatesTagTeamLifecycle
{
    /**
     * Determine if the tag team can be employed.
     *
     * Checks business rules for tag team employment:
     * - Must not already be employed
     * - Must not be retired (requires unretirement first)
     * - Partners must be available and employable
     * - Must meet promotion standards
     *
     * @return bool True if the tag team can be employed, false otherwise
     */
    public function canBeEmployed(): bool
    {
        if ($this->isEmployed()) {
            return false;
        }

        if ($this->isRetired()) {
            return false;
        }

        // Check if current partners are available for employment
        $currentPartners = $this->currentWrestlers;
        if ($currentPartners->isEmpty()) {
            return false;
        }

        // Check if any partner has conflicting employment
        $conflictedPartners = $currentPartners->filter(function ($wrestler) {
            return $wrestler->isEmployed() && method_exists($wrestler, 'hasExclusivityConflicts') && $wrestler->hasExclusivityConflicts();
        });

        if ($conflictedPartners->isNotEmpty()) {
            return false;
        }

        // Basic employment is possible
        return true;
    }

    /**
     * Ensure the tag team can be employed, throwing an exception if not.
     *
     * Validates that the tag team is in a valid state for employment while checking
     * for business rule violations including partner availability, standards compliance,
     * and administrative requirements.
     *
     * @throws CannotBeEmployedException When employment is not allowed
     */
    public function ensureCanBeEmployed(): void
    {
        if ($this->isEmployed()) {
            throw CannotBeEmployedException::alreadyEmployed($this);
        }

        if ($this->isRetired()) {
            throw CannotBeEmployedException::retired($this);
        }

        // Check partner availability
        $currentPartners = $this->currentWrestlers;
        if ($currentPartners->isEmpty()) {
            throw CannotBeEmployedException::partnersUnavailable($this, 'No current partners available');
        }

        // Check for partner employment conflicts
        $conflictedPartners = $currentPartners->filter(function ($wrestler) {
            return $wrestler->isEmployed() && method_exists($wrestler, 'hasExclusivityConflicts') && $wrestler->hasExclusivityConflicts();
        });

        if ($conflictedPartners->isNotEmpty()) {
            $partnerNames = $conflictedPartners->pluck('name')->join(', ');
            throw CannotBeEmployedException::partnerEmploymentConflicts($this, $partnerNames);
        }

        // Additional business rule validations could be added here:
        // - Check promotion employment standards
        // - Check roster limits
        // - Check authorization requirements
        // - Check disciplinary issues
    }

    /**
     * Determine if the tag team can be retired.
     *
     * Checks business rules for tag team retirement:
     * - Must be currently employed
     * - Must not already be retired
     * - Should validate championship obligations
     * - Should check for storyline conflicts
     *
     * @return bool True if the tag team can be retired, false otherwise
     */
    public function canBeRetired(): bool
    {
        if ($this->isRetired()) {
            return false;
        }

        if (! $this->isEmployed()) {
            return false;
        }

        // Basic retirement is possible if employed and not already retired
        return true;
    }

    /**
     * Ensure the tag team can be retired, throwing an exception if not.
     *
     * Validates that the tag team is in a valid state for retirement while checking
     * for business rule violations including championship obligations, storyline
     * conflicts, and administrative requirements.
     *
     * @throws CannotBeRetiredException When retirement is not allowed
     */
    public function ensureCanBeRetired(): void
    {
        if ($this->isRetired()) {
            throw CannotBeRetiredException::alreadyRetired($this);
        }

        if (! $this->isEmployed()) {
            throw CannotBeRetiredException::notEmployed($this);
        }

        // Additional business rule validations could be added here:
        // - Check for championship obligations
        // if ($this->hasCurrentChampionshipObligations()) {
        //     $championships = $this->getCurrentChampionshipDetails();
        //     throw CannotBeRetiredException::hasChampionshipObligations($this, $championships);
        // }

        // - Check for active storylines
        // if ($this->hasActiveStorylines()) {
        //     $storylines = $this->getActiveStorylineDetails();
        //     throw CannotBeRetiredException::storylineConflicts($this, $storylines);
        // }

        // - Check for scheduled matches
        // if ($this->hasScheduledMatches()) {
        //     $matches = $this->getScheduledMatchDetails();
        //     throw CannotBeRetiredException::hasScheduledMatches($this, $matches);
        // }

        // - Check partner contract conflicts
        // if ($this->hasPartnerContractConflicts()) {
        //     $conflicts = $this->getPartnerContractConflictDetails();
        //     throw CannotBeRetiredException::partnerContractConflicts($this, $conflicts);
        // }

        // - Check authorization requirements
        // if (! $this->hasRetirementAuthorization()) {
        //     throw CannotBeRetiredException::insufficientAuthorization($this, 'management');
        // }
    }

    /**
     * Determine if the tag team can be suspended.
     *
     * Checks business rules for tag team suspension:
     * - Must be currently employed
     * - Must not already be suspended
     * - Should validate disciplinary requirements
     *
     * @return bool True if the tag team can be suspended, false otherwise
     */
    public function canBeSuspended(): bool
    {
        if (! $this->isEmployed()) {
            return false;
        }

        if ($this->isSuspended()) {
            return false;
        }

        // Basic suspension is possible if employed and not already suspended
        return true;
    }

    /**
     * Ensure the tag team can be suspended, throwing an exception if not.
     *
     * Validates that the tag team is in a valid state for suspension while checking
     * for business rule violations including employment status, existing suspensions,
     * and administrative requirements.
     *
     * @throws CannotBeSuspendedException When suspension is not allowed
     */
    public function ensureCanBeSuspended(): void
    {
        if (! $this->isEmployed()) {
            throw CannotBeSuspendedException::notEmployed($this);
        }

        if ($this->isSuspended()) {
            throw CannotBeSuspendedException::alreadySuspended($this);
        }

        // Additional business rule validations could be added here:
        // - Check for disciplinary authorization requirements
        // - Check for active championship obligations
        // - Check for scheduled match conflicts
        // - Check for storyline impact considerations
    }

    /**
     * Determine if the tag team can be reinstated.
     *
     * Checks business rules for tag team reinstatement:
     * - Must be currently suspended
     * - Must still be employed
     * - Should validate reinstatement authorization
     *
     * @return bool True if the tag team can be reinstated, false otherwise
     */
    public function canBeReinstated(): bool
    {
        if (! $this->isSuspended()) {
            return false;
        }

        if (! $this->isEmployed()) {
            return false;
        }

        // Basic reinstatement is possible if suspended and employed
        return true;
    }

    /**
     * Ensure the tag team can be reinstated, throwing an exception if not.
     *
     * Validates that the tag team is in a valid state for reinstatement while checking
     * for business rule violations including suspension status, employment status,
     * and authorization requirements.
     *
     * @throws CannotBeReinstatedException When reinstatement is not allowed
     */
    public function ensureCanBeReinstated(): void
    {
        if (! $this->isSuspended()) {
            throw CannotBeReinstatedException::notSuspended($this);
        }

        if (! $this->isEmployed()) {
            throw CannotBeReinstatedException::notEmployed($this);
        }

        // Additional business rule validations could be added here:
        // - Check for reinstatement authorization requirements
        // - Check for disciplinary clearance
        // - Check for administrative approval
        // - Check for partner availability after suspension period
    }

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

    /**
     * Determine if the tag team can be unretired.
     *
     * Checks business rules for tag team unretirement:
     * - Must be currently retired
     * - Should have available current partners for viable reunion
     * - Name must not conflict with existing active tag teams
     *
     * @return bool True if the tag team can be unretired, false otherwise
     */
    public function canBeUnretired(): bool
    {
        if (! $this->isRetired()) {
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

        // Check if current partners are available
        $currentPartners = $this->currentWrestlers;
        if ($currentPartners->isEmpty()) {
            return false;
        }

        // Basic unretirement is possible if retired, no conflicts, and has partners
        return true;
    }

    /**
     * Ensure the tag team can be unretired, throwing an exception if not.
     *
     * Validates that the tag team is in a valid state for unretirement while checking
     * for business rule violations including partner availability, name conflicts,
     * and storyline considerations.
     *
     * @param  bool  $requireAvailablePartners  Whether to require available current partners
     * @throws CannotBeUnretiredException When unretirement is not allowed
     */
    public function ensureCanBeUnretired(bool $requireAvailablePartners = true): void
    {
        if (! $this->isRetired()) {
            throw CannotBeUnretiredException::notRetired($this);
        }

        // Check for name conflicts with existing active tag teams
        $conflictingTeam = static::where('name', $this->name)
            ->where('id', '!=', $this->id)
            ->whereHas('employments', function ($query) {
                $query->whereNull('ended_at');
            })
            ->first();

        if ($conflictingTeam) {
            throw CannotBeUnretiredException::nameConflict($this, $conflictingTeam->name);
        }

        if ($requireAvailablePartners) {
            // Check if current partners are available for unretirement
            $currentPartners = $this->currentWrestlers;

            if ($currentPartners->isEmpty()) {
                throw CannotBeUnretiredException::noAvailablePartners($this);
            }

            // Check minimum partner count for viable unretirement
            $minimumPartners = 2; // Tag teams require at least 2 partners
            if ($currentPartners->count() < $minimumPartners) {
                throw CannotBeUnretiredException::insufficientPartners(
                    $this,
                    $currentPartners->count(),
                    $minimumPartners
                );
            }

            // Check if key partners are available
            $unavailablePartners = $currentPartners->filter(function ($wrestler) {
                return (method_exists($wrestler, 'hasExclusivityConflicts') && $wrestler->hasExclusivityConflicts()) || $wrestler->isInjured();
            });

            if ($unavailablePartners->isNotEmpty()) {
                $partnerNames = $unavailablePartners->pluck('name')->join(', ');
                throw CannotBeUnretiredException::keyPartnersUnavailable($this, $partnerNames);
            }
        }

        // Additional business rule validations could be added here:
        // - Check for storyline conflicts with current active teams
        // - Check partner commitment conflicts with current partnerships
        // - Check administrative authorization requirements
        // - Check event timing conflicts
    }
}

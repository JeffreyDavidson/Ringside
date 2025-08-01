<?php

declare(strict_types=1);

namespace App\Services;

use App\Exceptions\Data\CannotBeRestoredException;
use App\Exceptions\Roster\CannotBeClearedFromInjuryException;
use App\Exceptions\Roster\CannotBeEmployedException;
use App\Exceptions\Roster\CannotBeInjuredException;
use App\Exceptions\Roster\CannotBeReleasedException;
use App\Exceptions\Roster\CannotBeRetiredException;
use App\Exceptions\Roster\CannotBeSuspendedException;
use App\Exceptions\Roster\CannotBeUnretiredException;
use App\Exceptions\Status\CannotBeReinstatedException;
use Throwable;

/**
 * Service to map technical exceptions to user-friendly error messages.
 *
 * This service provides a centralized way to convert detailed business
 * exception messages into user-friendly error messages while preserving
 * the technical details for logging and debugging purposes.
 */
class ErrorMessageMappingService
{
    /**
     * Map a wrestler-related exception to a user-friendly error message key.
     *
     * @param  Throwable  $exception  The exception to map
     * @return string Language file key for user-friendly error message
     */
    public static function mapWrestlerException(Throwable $exception): string
    {
        $exceptionMessage = $exception->getMessage();

        return match (get_class($exception)) {
            CannotBeEmployedException::class => self::mapEmploymentException($exceptionMessage),
            CannotBeReleasedException::class => self::mapReleaseException($exceptionMessage),
            CannotBeRetiredException::class => self::mapRetirementException($exceptionMessage),
            CannotBeUnretiredException::class => self::mapUnretirementException($exceptionMessage),
            CannotBeSuspendedException::class => self::mapSuspensionException($exceptionMessage),
            CannotBeReinstatedException::class => self::mapReinstatementException($exceptionMessage),
            CannotBeInjuredException::class => self::mapInjuryException($exceptionMessage),
            CannotBeClearedFromInjuryException::class => self::mapHealingException($exceptionMessage),
            CannotBeRestoredException::class => self::mapRestorationException($exceptionMessage),
            default => 'wrestlers.errors.general_error',
        };
    }

    /**
     * Map a referee-related exception to a user-friendly error message key.
     *
     * @param  Throwable  $exception  The exception to map
     * @return string Language file key for user-friendly error message
     */
    public static function mapRefereeException(Throwable $exception): string
    {
        $exceptionMessage = $exception->getMessage();

        return match (get_class($exception)) {
            CannotBeEmployedException::class => self::mapRefereeEmploymentException($exceptionMessage),
            CannotBeReleasedException::class => self::mapRefereeReleaseException($exceptionMessage),
            CannotBeRetiredException::class => self::mapRefereeRetirementException($exceptionMessage),
            CannotBeUnretiredException::class => self::mapRefereeUnretirementException($exceptionMessage),
            CannotBeSuspendedException::class => self::mapRefereeSuspensionException($exceptionMessage),
            CannotBeReinstatedException::class => self::mapRefereeReinstatementException($exceptionMessage),
            CannotBeInjuredException::class => self::mapRefereeInjuryException($exceptionMessage),
            CannotBeClearedFromInjuryException::class => self::mapRefereeHealingException($exceptionMessage),
            CannotBeRestoredException::class => self::mapRefereeRestorationException($exceptionMessage),
            default => 'referees.errors.general_error',
        };
    }

    /**
     * Map employment-specific exception messages to user-friendly keys.
     */
    private static function mapEmploymentException(string $message): string
    {
        if (str_contains($message, 'already employed')) {
            return 'wrestlers.errors.already_employed';
        }

        if (str_contains($message, 'suspended')) {
            return 'wrestlers.errors.cannot_employ_suspended';
        }

        if (str_contains($message, 'retired')) {
            return 'wrestlers.errors.cannot_employ_retired';
        }

        return 'wrestlers.errors.cannot_employ';
    }

    /**
     * Map release-specific exception messages to user-friendly keys.
     */
    private static function mapReleaseException(string $message): string
    {
        if (str_contains($message, 'unemployed') || str_contains($message, 'not employed')) {
            return 'wrestlers.errors.not_employed';
        }

        return 'wrestlers.errors.cannot_release';
    }

    /**
     * Map retirement-specific exception messages to user-friendly keys.
     */
    private static function mapRetirementException(string $message): string
    {
        if (str_contains($message, 'unemployed') || str_contains($message, 'not employed')) {
            return 'wrestlers.errors.cannot_retire_unemployed';
        }

        if (str_contains($message, 'already retired')) {
            return 'wrestlers.errors.already_retired';
        }

        return 'wrestlers.errors.cannot_retire';
    }

    /**
     * Map unretirement-specific exception messages to user-friendly keys.
     */
    private static function mapUnretirementException(string $message): string
    {
        if (str_contains($message, 'not retired')) {
            return 'wrestlers.errors.not_retired';
        }

        return 'wrestlers.errors.cannot_unretire';
    }

    /**
     * Map suspension-specific exception messages to user-friendly keys.
     */
    private static function mapSuspensionException(string $message): string
    {
        if (str_contains($message, 'already suspended')) {
            return 'wrestlers.errors.already_suspended';
        }

        return 'wrestlers.errors.cannot_suspend';
    }

    /**
     * Map reinstatement-specific exception messages to user-friendly keys.
     */
    private static function mapReinstatementException(string $message): string
    {
        if (str_contains($message, 'not suspended') && str_contains($message, 'not injured')) {
            return 'wrestlers.errors.not_suspended_or_injured';
        }

        return 'wrestlers.errors.cannot_reinstate';
    }

    /**
     * Map injury-specific exception messages to user-friendly keys.
     */
    private static function mapInjuryException(string $message): string
    {
        if (str_contains($message, 'already injured')) {
            return 'wrestlers.errors.already_injured';
        }

        return 'wrestlers.errors.cannot_injure';
    }

    /**
     * Map healing-specific exception messages to user-friendly keys.
     */
    private static function mapHealingException(string $message): string
    {
        if (str_contains($message, 'not injured')) {
            return 'wrestlers.errors.not_injured';
        }

        return 'wrestlers.errors.cannot_heal';
    }

    /**
     * Map restoration-specific exception messages to user-friendly keys.
     */
    private static function mapRestorationException(string $message): string
    {
        if (str_contains($message, 'not deleted')) {
            return 'wrestlers.errors.not_deleted';
        }

        return 'wrestlers.errors.cannot_restore';
    }

    /**
     * Map referee employment-specific exception messages to user-friendly keys.
     */
    private static function mapRefereeEmploymentException(string $message): string
    {
        if (str_contains($message, 'already employed')) {
            return 'referees.errors.already_employed';
        }

        if (str_contains($message, 'suspended')) {
            return 'referees.errors.cannot_employ_suspended';
        }

        if (str_contains($message, 'retired')) {
            return 'referees.errors.cannot_employ_retired';
        }

        return 'referees.errors.cannot_employ';
    }

    /**
     * Map referee release-specific exception messages to user-friendly keys.
     */
    private static function mapRefereeReleaseException(string $message): string
    {
        if (str_contains($message, 'unemployed') || str_contains($message, 'not employed')) {
            return 'referees.errors.not_employed';
        }

        return 'referees.errors.cannot_release';
    }

    /**
     * Map referee retirement-specific exception messages to user-friendly keys.
     */
    private static function mapRefereeRetirementException(string $message): string
    {
        if (str_contains($message, 'unemployed') || str_contains($message, 'not employed')) {
            return 'referees.errors.cannot_retire_unemployed';
        }

        if (str_contains($message, 'already retired')) {
            return 'referees.errors.already_retired';
        }

        return 'referees.errors.cannot_retire';
    }

    /**
     * Map referee unretirement-specific exception messages to user-friendly keys.
     */
    private static function mapRefereeUnretirementException(string $message): string
    {
        if (str_contains($message, 'not retired')) {
            return 'referees.errors.not_retired';
        }

        return 'referees.errors.cannot_unretire';
    }

    /**
     * Map referee suspension-specific exception messages to user-friendly keys.
     */
    private static function mapRefereeSuspensionException(string $message): string
    {
        if (str_contains($message, 'already suspended')) {
            return 'referees.errors.already_suspended';
        }

        if (str_contains($message, 'unemployed') || str_contains($message, 'not employed')) {
            return 'referees.errors.cannot_suspend_unemployed';
        }

        return 'referees.errors.cannot_suspend';
    }

    /**
     * Map referee reinstatement-specific exception messages to user-friendly keys.
     */
    private static function mapRefereeReinstatementException(string $message): string
    {
        if (str_contains($message, 'not suspended')) {
            return 'referees.errors.not_suspended';
        }

        return 'referees.errors.cannot_reinstate';
    }

    /**
     * Map referee injury-specific exception messages to user-friendly keys.
     */
    private static function mapRefereeInjuryException(string $message): string
    {
        if (str_contains($message, 'already injured')) {
            return 'referees.errors.already_injured';
        }

        if (str_contains($message, 'unemployed') || str_contains($message, 'not employed')) {
            return 'referees.errors.cannot_injure_unemployed';
        }

        return 'referees.errors.cannot_injure';
    }

    /**
     * Map referee healing-specific exception messages to user-friendly keys.
     */
    private static function mapRefereeHealingException(string $message): string
    {
        if (str_contains($message, 'not injured')) {
            return 'referees.errors.not_injured';
        }

        return 'referees.errors.cannot_heal';
    }

    /**
     * Map referee restoration-specific exception messages to user-friendly keys.
     */
    private static function mapRefereeRestorationException(string $message): string
    {
        if (str_contains($message, 'not deleted')) {
            return 'referees.errors.not_deleted';
        }

        return 'referees.errors.cannot_restore';
    }
}

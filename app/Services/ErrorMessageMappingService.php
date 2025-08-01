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
}

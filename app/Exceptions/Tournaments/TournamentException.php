<?php

declare(strict_types=1);

namespace App\Exceptions\Tournaments;

use App\Exceptions\BaseBusinessException;

final class TournamentException extends BaseBusinessException
{
    public static function alreadyBooked(): self
    {
        return new self(__('tournaments.errors.already_booked'));
    }

    public static function chronology(): self
    {
        return new self(__('tournaments.errors.chronology'));
    }

    public static function closed(): self
    {
        return new self(__('tournaments.errors.closed'));
    }

    public static function dependentBookings(): self
    {
        return new self(__('tournaments.errors.dependent_bookings'));
    }

    public static function entrantUnavailable(): self
    {
        return new self(__('tournaments.errors.entrant_unavailable'));
    }

    public static function entrantsChanged(): self
    {
        return new self(__('tournaments.errors.entrants_changed'));
    }

    public static function entryPlayed(): self
    {
        return new self(__('tournaments.errors.entry_played'));
    }

    public static function finalTitlesOnly(): self
    {
        return new self(__('tournaments.errors.final_titles_only'));
    }

    public static function historicalAttempt(): self
    {
        return new self(__('tournaments.errors.historical_attempt'));
    }

    public static function linkedDelete(): self
    {
        return new self(__('tournaments.errors.linked_delete'));
    }

    public static function playedDependency(): self
    {
        return new self(__('tournaments.errors.played_dependency'));
    }

    public static function structureFrozen(): self
    {
        return new self(__('tournaments.errors.structure_frozen'));
    }

    public static function unresolved(): self
    {
        return new self(__('tournaments.errors.unresolved'));
    }

    public static function walkoverInvalid(): self
    {
        return new self(__('tournaments.errors.walkover_invalid'));
    }
}

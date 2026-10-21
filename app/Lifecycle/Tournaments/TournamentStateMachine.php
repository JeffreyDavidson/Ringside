<?php

declare(strict_types=1);

namespace App\Lifecycle\Tournaments;

use App\Enums\Tournaments\TournamentStatus;
use App\Exceptions\Tournaments\TournamentException;
use App\Models\Tournaments\Tournament;

/**
 * The status rules of a tournament: Draft until its first bout is booked, Active while it runs, and Completed or
 * Cancelled once it is over.
 *
 * It reads the tournament and refuses with TournamentException; it never locks or writes. The Actions lock the
 * tournament (TournamentLocks) and set the status themselves. The moves table lists only the moves an Action asks
 * for (booking a bout makes the tournament Active, cancelling makes it Cancelled). The shift between Active and
 * Completed is computed from the bracket by TournamentOutcomeManager::synchronize and is never asked for.
 */
final readonly class TournamentStateMachine
{
    /**
     * The moves an Action may ask for, by the status the tournament is in (the key is the status value). Anything
     * not listed is refused. Booking another bout keeps an Active tournament Active.
     */
    private const array MOVES = [
        'draft' => [TournamentStatus::Active, TournamentStatus::Cancelled],
        'active' => [TournamentStatus::Active, TournamentStatus::Cancelled],
        'completed' => [],
        'cancelled' => [],
    ];

    public function canMove(TournamentStatus $from, TournamentStatus $to): bool
    {
        return in_array($to, self::MOVES[$from->value], true);
    }

    /** Booking the first bout of a Draft tournament is what makes it Active. */
    public function activates(Tournament $tournament): bool
    {
        return $tournament->status === TournamentStatus::Draft;
    }

    /**
     * Refuse a move the table does not allow.
     *
     * @throws TournamentException
     */
    public function ensureCanMove(Tournament $tournament, TournamentStatus $to): void
    {
        if (! $this->canMove($tournament->status, $to)) {
            throw TournamentException::closed();
        }
    }

    /**
     * Refuse planning a bout on a tournament that can no longer take a booking.
     *
     * @throws TournamentException
     */
    public function ensureOpen(Tournament $tournament): void
    {
        $this->ensureCanMove($tournament, TournamentStatus::Active);
    }

    /**
     * Refuse a withdrawal, replacement or walkover unless the tournament is running.
     *
     * @throws TournamentException
     */
    public function ensureActive(Tournament $tournament): void
    {
        if ($tournament->status !== TournamentStatus::Active) {
            throw TournamentException::closed();
        }
    }

    /**
     * Refuse changing the entrants or capacity once the tournament has left Draft.
     *
     * @throws TournamentException
     */
    public function ensureStructureEditable(Tournament $tournament): void
    {
        if ($tournament->status !== TournamentStatus::Draft) {
            throw TournamentException::structureFrozen();
        }
    }

    /** A cancelled tournament keeps its status whatever its results do. */
    public function canProgress(Tournament $tournament): bool
    {
        return $tournament->status !== TournamentStatus::Cancelled;
    }

    /**
     * Refuse a result change on a cancelled tournament.
     *
     * @throws TournamentException
     */
    public function ensureCanProgress(Tournament $tournament): void
    {
        if (! $this->canProgress($tournament)) {
            throw TournamentException::closed();
        }
    }
}

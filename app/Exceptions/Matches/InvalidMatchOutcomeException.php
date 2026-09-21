<?php

declare(strict_types=1);

namespace App\Exceptions\Matches;

use App\Enums\Titles\TitleType;
use App\Exceptions\BaseBusinessException;

final class InvalidMatchOutcomeException extends BaseBusinessException
{
    public static function missingWinningSide(): self
    {
        return new self('This match finish requires a winning side.');
    }

    public static function unexpectedWinningSide(): self
    {
        return new self('This match finish cannot have a winning side.');
    }

    public static function winningSideFromAnotherMatch(): self
    {
        return new self('The winning side must belong to the match being resulted.');
    }

    public static function winningSideWithoutCompetitors(): self
    {
        return new self('The winning side must contain at least one competitor.');
    }

    public static function eliminationsNotSupported(): self
    {
        return new self('Elimination details may only be recorded for Battle Royal and Royal Rumble matches.');
    }

    public static function competitorFromAnotherMatch(): self
    {
        return new self('Every eliminated competitor must belong to the match being resulted.');
    }

    public static function eliminatorFromAnotherMatch(): self
    {
        return new self('Every eliminating competitor must belong to the match being resulted.');
    }

    public static function selfElimination(): self
    {
        return new self('A competitor cannot eliminate itself.');
    }

    public static function duplicateEliminatedCompetitor(): self
    {
        return new self('A competitor may be eliminated only once.');
    }

    public static function invalidEliminationOrder(): self
    {
        return new self('Elimination order must be unique, positive, and consecutive.');
    }

    public static function incompleteEliminationHistory(): self
    {
        return new self('A decisive elimination match must record every losing competitor exactly once.');
    }

    public static function winnerEliminated(): self
    {
        return new self('A competitor on the winning side cannot be eliminated.');
    }

    public static function eliminationAfterEliminatorExited(): self
    {
        return new self('A competitor cannot eliminate another competitor after being eliminated.');
    }

    public static function invalidEntryOrder(): self
    {
        return new self('Royal Rumble entry order must be unique, positive, and consecutive.');
    }

    public static function invalidTitleWinner(TitleType $titleType): self
    {
        return new self("The winning side must contain exactly one {$titleType->value} championship competitor.");
    }

    public static function undatedTitleMatch(): self
    {
        return new self('A title change cannot be recorded for an event without a date.');
    }

    public static function titleLineageHasAdvanced(): self
    {
        return new self('This result cannot be corrected because a later title reign depends on it.');
    }
}

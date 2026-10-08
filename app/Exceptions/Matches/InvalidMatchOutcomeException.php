<?php

declare(strict_types=1);

namespace App\Exceptions\Matches;

use App\Enums\Titles\TitleType;
use App\Exceptions\BaseBusinessException;
use App\Models\Roster\TagTeams\TagTeam;
use App\Models\Roster\Wrestlers\Wrestler;
use App\Models\Titles\Title;

final class InvalidMatchOutcomeException extends BaseBusinessException
{
    public static function missingWinningSide(): self
    {
        return new self(__('matches.errors.outcome.missing_winning_side'));
    }

    public static function unexpectedWinningSide(): self
    {
        return new self(__('matches.errors.outcome.unexpected_winning_side'));
    }

    public static function winningSideFromAnotherMatch(): self
    {
        return new self(__('matches.errors.outcome.winning_side_from_another_match'));
    }

    public static function winningSideWithoutCompetitors(): self
    {
        return new self(__('matches.errors.outcome.winning_side_without_competitors'));
    }

    public static function eliminationsNotSupported(): self
    {
        return new self(__('matches.errors.outcome.eliminations_not_supported'));
    }

    public static function competitorFromAnotherMatch(): self
    {
        return new self(__('matches.errors.outcome.competitor_from_another_match'));
    }

    public static function eliminatorFromAnotherMatch(): self
    {
        return new self(__('matches.errors.outcome.eliminator_from_another_match'));
    }

    public static function selfElimination(): self
    {
        return new self(__('matches.errors.outcome.self_elimination'));
    }

    public static function duplicateEliminatedCompetitor(): self
    {
        return new self(__('matches.errors.outcome.duplicate_eliminated_competitor'));
    }

    public static function invalidEliminationOrder(): self
    {
        return new self(__('matches.errors.outcome.invalid_elimination_order'));
    }

    public static function incompleteEliminationHistory(): self
    {
        return new self(__('matches.errors.outcome.incomplete_elimination_history'));
    }

    public static function winnerEliminated(): self
    {
        return new self(__('matches.errors.outcome.winner_eliminated'));
    }

    public static function eliminationAfterEliminatorExited(): self
    {
        return new self(__('matches.errors.outcome.elimination_after_eliminator_exited'));
    }

    public static function invalidEntryOrder(): self
    {
        return new self(__('matches.errors.outcome.invalid_entry_order'));
    }

    public static function invalidTitleWinner(TitleType $titleType): self
    {
        return new self(__('matches.errors.outcome.invalid_title_winner', ['title_type' => $titleType->value]));
    }

    public static function undatedTitleMatch(): self
    {
        return new self(__('matches.errors.outcome.undated_title_match'));
    }

    public static function titleLineageHasAdvanced(): self
    {
        return new self(__('matches.errors.outcome.title_lineage_has_advanced'));
    }

    public static function titleResultOutOfDateOrder(Title $title): self
    {
        return new self(__('matches.errors.outcome.title_result_out_of_date_order', ['title' => $title->name]));
    }

    public static function eventNotHeld(): self
    {
        return new self(__('matches.errors.outcome.event_not_held'));
    }

    public static function titleNotActive(Title $title): self
    {
        return new self(__('matches.errors.outcome.title_not_active', ['title' => $title->name]));
    }

    public static function titleDeleted(): self
    {
        return new self(__('matches.errors.outcome.title_deleted'));
    }

    public static function winnerNotEligible(Wrestler|TagTeam $winner): self
    {
        return new self(__('matches.errors.outcome.winner_not_eligible', ['name' => $winner->name]));
    }

    public static function winnerDeleted(): self
    {
        return new self(__('matches.errors.outcome.winner_deleted'));
    }
}

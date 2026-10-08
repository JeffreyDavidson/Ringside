<?php

declare(strict_types=1);

namespace App\Exceptions\Matches;

use App\Enums\BusinessRuleReason;
use App\Enums\MatchType;
use App\Exceptions\BaseBusinessException;
use App\Models\Titles\Title;

final class InvalidMatchConfigurationException extends BaseBusinessException
{
    public static function incorrectSideCount(int $requiredSides): static
    {
        return new self(__('matches.errors.configuration.incorrect_side_count', ['sides' => $requiredSides]));
    }

    public static function invalidCompetitorCount(int $minimumCompetitors, ?int $maximumCompetitors): static
    {
        if ($maximumCompetitors === null) {
            return new self(__('matches.errors.configuration.competitor_count_minimum', ['minimum' => $minimumCompetitors]));
        }

        return new self(__('matches.errors.configuration.competitor_count_between', ['minimum' => $minimumCompetitors, 'maximum' => $maximumCompetitors]));
    }

    public static function duplicateCompetitors(): static
    {
        return new self(__('matches.errors.configuration.duplicate_competitors'));
    }

    public static function duplicateCompetitorRepresentation(): static
    {
        return new self(__('matches.errors.configuration.duplicate_representation'));
    }

    public static function unsupportedCompetitorType(MatchType $matchType): static
    {
        return new self(__('matches.errors.configuration.unsupported_competitor_type', ['match_type' => $matchType->label()]));
    }

    /** @param list<int> $requiredRosterMembersPerSide */
    public static function invalidSideComposition(MatchType $matchType, array $requiredRosterMembersPerSide): static
    {
        $composition = implode('-on-', $requiredRosterMembersPerSide);

        return new self(__('matches.errors.configuration.invalid_side_composition', ['match_type' => $matchType->label(), 'composition' => $composition]));
    }

    /** @param list<int> $requiredCompetitorEntriesPerSide */
    public static function invalidCompetitorEntryComposition(MatchType $matchType, array $requiredCompetitorEntriesPerSide): static
    {
        $composition = implode('-on-', $requiredCompetitorEntriesPerSide);

        return new self(__('matches.errors.configuration.invalid_entry_composition', ['match_type' => $matchType->label(), 'composition' => $composition]));
    }

    public static function individualCompetitorSidesRequired(MatchType $matchType): static
    {
        return new self(__('matches.errors.configuration.individual_sides_required', ['match_type' => $matchType->label()]));
    }

    public static function invalidSideNumber(int $sideNumber): static
    {
        return new self(__('matches.errors.configuration.invalid_side_number', ['side' => $sideNumber]));
    }

    public static function missingCompetitors(): static
    {
        return new self(__('matches.errors.configuration.missing_competitors'));
    }

    public static function missingReferees(): static
    {
        return new self(__('matches.errors.configuration.missing_referees'));
    }

    public static function outsideEventPromotion(string $entityType): static
    {
        return new self(__('matches.errors.configuration.outside_event_promotion', ['entity_type' => $entityType]));
    }

    public static function resultAlreadyRecorded(): static
    {
        return new self(__('matches.errors.configuration.result_already_recorded'));
    }

    public static function currentChampionMissing(Title $title): static
    {
        return self::forReason(
            BusinessRuleReason::CurrentChampionMissing,
            __('matches.errors.configuration.current_champion_missing', ['title' => $title->name]),
        );
    }

    public static function titleCompetitorTypeMismatch(Title $title): static
    {
        return new self(__('matches.errors.configuration.title_competitor_type_mismatch', ['title' => $title->name]));
    }
}

<?php

declare(strict_types=1);

namespace App\Exceptions\Matches;

use App\Enums\BusinessRuleReason;
use App\Exceptions\BaseBusinessException;
use App\Models\Titles\Title;

final class InvalidMatchConfigurationException extends BaseBusinessException
{
    public static function incorrectSideCount(int $requiredSides): static
    {
        return new self("This match requires exactly {$requiredSides} competitor sides.");
    }

    public static function invalidCompetitorCount(int $minimumCompetitors, ?int $maximumCompetitors): static
    {
        if ($maximumCompetitors === null) {
            return new self("This match requires at least {$minimumCompetitors} competitors.");
        }

        return new self("This match requires between {$minimumCompetitors} and {$maximumCompetitors} competitors.");
    }

    public static function duplicateCompetitors(): static
    {
        return new self('The same competitor cannot compete multiple times in a match.');
    }

    public static function invalidSideNumber(int $sideNumber): static
    {
        return new self("Match side number [{$sideNumber}] must be positive.");
    }

    public static function missingCompetitors(): static
    {
        return new self('A match must have competitors assigned.');
    }

    public static function missingReferees(): static
    {
        return new self('A match must have at least one referee assigned.');
    }

    public static function resultAlreadyRecorded(): static
    {
        return new self('A match cannot be reconfigured after its result has been recorded.');
    }

    public static function currentChampionMissing(Title $title): static
    {
        return self::forReason(
            BusinessRuleReason::CurrentChampionMissing,
            "The current champion of [{$title->name}] must compete in the title match.",
        );
    }
}

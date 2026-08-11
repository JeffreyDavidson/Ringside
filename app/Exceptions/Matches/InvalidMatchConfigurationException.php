<?php

declare(strict_types=1);

namespace App\Exceptions\Matches;

use App\Exceptions\BaseBusinessException;

final class InvalidMatchConfigurationException extends BaseBusinessException
{
    public static function insufficientSides(int $minimumSides): static
    {
        return new self("A match must have competitors assigned to at least {$minimumSides} sides.");
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
}

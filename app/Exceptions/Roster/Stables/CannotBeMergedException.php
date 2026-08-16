<?php

declare(strict_types=1);

namespace App\Exceptions\Roster\Stables;

use App\Exceptions\BaseBusinessException;
use App\Models\Roster\Stables\Stable;

final class CannotBeMergedException extends BaseBusinessException
{
    public static function selfMerge(Stable $stable): static
    {
        $context = self::formatModelContext($stable);

        return new self("{$context} cannot be merged with itself.");
    }

    public static function primaryRetired(Stable $primaryStable): static
    {
        $context = self::formatModelContext($primaryStable);

        return new self("{$context} is retired and cannot receive merged members.");
    }

    public static function secondaryRetired(Stable $secondaryStable): static
    {
        $context = self::formatModelContext($secondaryStable);

        return new self("{$context} is retired and cannot be merged.");
    }

    public static function primaryNotActive(Stable $primaryStable): static
    {
        $context = self::formatModelContext($primaryStable);

        return new self("{$context} is not currently active and cannot receive merged members.");
    }

    public static function secondaryNotActive(Stable $secondaryStable): static
    {
        $context = self::formatModelContext($secondaryStable);

        return new self("{$context} is not currently active and cannot be merged.");
    }

    /** @param array<int, string> $memberNames */
    public static function membersUnavailable(array $memberNames): static
    {
        return new self('Cannot merge stables: these secondary stable members are unavailable: '.implode(', ', $memberNames).'.');
    }
}

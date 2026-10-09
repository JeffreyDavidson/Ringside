<?php

declare(strict_types=1);

namespace App\Exceptions\Roster\TagTeams;

use App\Enums\BusinessRuleReason;
use App\Exceptions\BaseBusinessException;
use App\Models\Roster\TagTeams\TagTeam;

final class CannotBeUnretiredException extends BaseBusinessException
{
    public static function notRetired(TagTeam $tagTeam): static
    {
        $context = self::formatModelContext($tagTeam);

        return self::forReason(BusinessRuleReason::NotRetired, "{$context} is not retired and cannot be unretired.");
    }

    public static function nameConflict(TagTeam $tagTeam, string $conflictingTeamName): static
    {
        $context = self::formatModelContext($tagTeam);

        return new self("{$context} cannot be unretired: name conflicts with existing tag team '{$conflictingTeamName}'.");
    }

    public static function noAvailablePartners(TagTeam $tagTeam): static
    {
        $context = self::formatModelContext($tagTeam);

        return new self("{$context} cannot be unretired: no current partners are available.");
    }

    public static function insufficientPartners(TagTeam $tagTeam, int $availableCount, int $minimumRequired): static
    {
        $context = self::formatModelContext($tagTeam);

        return new self("{$context} cannot be unretired: only {$availableCount} partners available, but {$minimumRequired} required.");
    }

    public static function keyPartnersUnavailable(TagTeam $tagTeam, string $unavailablePartners): static
    {
        $context = self::formatModelContext($tagTeam);

        return new self("{$context} cannot be unretired: key partners unavailable: {$unavailablePartners}.");
    }

    public static function partnerDeleted(TagTeam $tagTeam, string $partnerName): static
    {
        $context = self::formatModelContext($tagTeam);

        return new self("{$context} cannot be unretired: partner {$partnerName} has been deleted.");
    }

    public static function partnerOnAnotherTagTeam(TagTeam $tagTeam, string $partnerName, string $otherTagTeamName): static
    {
        $context = self::formatModelContext($tagTeam);

        return new self("{$context} cannot be unretired: partner {$partnerName} is now on tag team '{$otherTagTeamName}'.");
    }
}

<?php

declare(strict_types=1);

namespace App\Exceptions\Roster\TagTeams;

use App\Enums\Shared\BusinessRuleReason;
use App\Exceptions\BaseBusinessException;
use App\Models\Roster\TagTeams\TagTeam;

final class CannotBeUnretiredException extends BaseBusinessException
{
    public static function notRetired(TagTeam $tagTeam): static
    {
        return self::forModel('tag-team-lifecycle.errors.unretire.not_retired', $tagTeam, reason: BusinessRuleReason::NotRetired);
    }

    public static function nameConflict(TagTeam $tagTeam, string $conflictingTeamName): static
    {
        return self::forModel('tag-team-lifecycle.errors.unretire.name_conflict', $tagTeam, ['name' => $conflictingTeamName]);
    }

    public static function noAvailablePartners(TagTeam $tagTeam): static
    {
        return self::forModel('tag-team-lifecycle.errors.unretire.no_available_partners', $tagTeam);
    }

    public static function insufficientPartners(TagTeam $tagTeam, int $availableCount, int $minimumRequired): static
    {
        return self::forModel('tag-team-lifecycle.errors.unretire.insufficient_partners', $tagTeam, ['required' => $minimumRequired], count: $availableCount);
    }

    public static function keyPartnersUnavailable(TagTeam $tagTeam, string $unavailablePartners): static
    {
        return self::forModel('tag-team-lifecycle.errors.unretire.key_partners_unavailable', $tagTeam, ['partners' => $unavailablePartners]);
    }

    public static function partnerDeleted(TagTeam $tagTeam, string $partnerName): static
    {
        return self::forModel('tag-team-lifecycle.errors.unretire.partner_deleted', $tagTeam, ['wrestler' => $partnerName], reason: BusinessRuleReason::PartnerDeleted)
            ->withTranslationReplacements(['wrestler' => $partnerName]);
    }

    public static function partnerOnAnotherTagTeam(TagTeam $tagTeam, string $partnerName, string $otherTagTeamName): static
    {
        return self::forModel('tag-team-lifecycle.errors.unretire.partner_on_another_tag_team', $tagTeam, ['wrestler' => $partnerName, 'tag_team' => $otherTagTeamName], reason: BusinessRuleReason::PartnerOnAnotherTagTeam)
            ->withTranslationReplacements(['wrestler' => $partnerName, 'tag_team' => $otherTagTeamName]);
    }
}

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
        $context = self::formatModelContext($tagTeam);

        return self::forReason(BusinessRuleReason::NotRetired, __('tag-team-lifecycle.errors.unretire.not_retired', ['context' => $context]));
    }

    public static function nameConflict(TagTeam $tagTeam, string $conflictingTeamName): static
    {
        $context = self::formatModelContext($tagTeam);

        return new self(__('tag-team-lifecycle.errors.unretire.name_conflict', ['context' => $context, 'name' => $conflictingTeamName]));
    }

    public static function noAvailablePartners(TagTeam $tagTeam): static
    {
        $context = self::formatModelContext($tagTeam);

        return new self(__('tag-team-lifecycle.errors.unretire.no_available_partners', ['context' => $context]));
    }

    public static function insufficientPartners(TagTeam $tagTeam, int $availableCount, int $minimumRequired): static
    {
        $context = self::formatModelContext($tagTeam);

        return new self(trans_choice('tag-team-lifecycle.errors.unretire.insufficient_partners', $availableCount, ['context' => $context, 'required' => $minimumRequired]));
    }

    public static function keyPartnersUnavailable(TagTeam $tagTeam, string $unavailablePartners): static
    {
        $context = self::formatModelContext($tagTeam);

        return new self(__('tag-team-lifecycle.errors.unretire.key_partners_unavailable', ['context' => $context, 'partners' => $unavailablePartners]));
    }

    public static function partnerDeleted(TagTeam $tagTeam, string $partnerName): static
    {
        $context = self::formatModelContext($tagTeam);

        return self::forReason(BusinessRuleReason::PartnerDeleted, __('tag-team-lifecycle.errors.unretire.partner_deleted', ['context' => $context, 'wrestler' => $partnerName]))
            ->withTranslationReplacements(['wrestler' => $partnerName]);
    }

    public static function partnerOnAnotherTagTeam(TagTeam $tagTeam, string $partnerName, string $otherTagTeamName): static
    {
        $context = self::formatModelContext($tagTeam);

        return self::forReason(BusinessRuleReason::PartnerOnAnotherTagTeam, __('tag-team-lifecycle.errors.unretire.partner_on_another_tag_team', ['context' => $context, 'wrestler' => $partnerName, 'tag_team' => $otherTagTeamName]))
            ->withTranslationReplacements(['wrestler' => $partnerName, 'tag_team' => $otherTagTeamName]);
    }
}

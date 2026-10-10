<?php

declare(strict_types=1);

namespace App\Exceptions\Roster\TagTeams;

use App\Enums\BusinessRuleReason;
use App\Exceptions\BaseBusinessException;

final class NameTakenException extends BaseBusinessException
{
    public static function name(string $name): static
    {
        return self::forReason(BusinessRuleReason::NameTaken, __('tag-teams.validation.name_taken', ['name' => $name]));
    }

    public static function signatureMove(string $signatureMove): static
    {
        return self::forReason(BusinessRuleReason::SignatureMoveTaken, __('tag-teams.validation.signature_move_taken', ['signature_move' => $signatureMove]));
    }
}

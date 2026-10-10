<?php

declare(strict_types=1);

namespace App\Exceptions\Roster\Wrestlers;

use App\Enums\BusinessRuleReason;
use App\Exceptions\BaseBusinessException;

final class NameTakenException extends BaseBusinessException
{
    public static function name(string $name): static
    {
        return self::forReason(BusinessRuleReason::NameTaken, __('wrestlers.validation.name_taken', ['name' => $name]));
    }

    public static function signatureMove(string $signatureMove): static
    {
        return self::forReason(BusinessRuleReason::SignatureMoveTaken, __('wrestlers.validation.signature_move_taken', ['signature_move' => $signatureMove]));
    }
}

<?php

declare(strict_types=1);

namespace App\Exceptions\Titles;

use App\Exceptions\BaseBusinessException;
use App\Models\Titles\Title;

final class CannotBeDebutedException extends BaseBusinessException
{
    public static function alreadyDebuted(Title $title): static
    {
        $context = self::formatModelContext($title);

        return new self(__('titles.errors.debuted.already_debuted', ['context' => $context]));
    }
}

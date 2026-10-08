<?php

declare(strict_types=1);

namespace App\Exceptions\Promotions;

use App\Exceptions\BaseBusinessException;
use App\Models\Promotions\Promotion;

final class CannotRemoveLastOwnerException extends BaseBusinessException
{
    public static function lastActiveOwner(Promotion $promotion): static
    {
        return new self(__('promotions.last_owner', ['name' => $promotion->name]));
    }
}

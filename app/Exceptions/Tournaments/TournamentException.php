<?php

declare(strict_types=1);

namespace App\Exceptions\Tournaments;

use App\Exceptions\BaseBusinessException;

final class TournamentException extends BaseBusinessException
{
    public static function because(string $reason): self
    {
        return new self(__('tournaments.errors.'.$reason));
    }
}

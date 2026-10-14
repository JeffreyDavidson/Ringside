<?php

declare(strict_types=1);

namespace App\Exceptions\Sponsors;

use App\Exceptions\BaseBusinessException;

final class SponsorCommitmentException extends BaseBusinessException
{
    public static function because(string $key): static
    {
        return new self(__('sponsors.'.$key));
    }
}

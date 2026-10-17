<?php

declare(strict_types=1);

namespace App\Support;

use Illuminate\Support\Str;

final class EmailAddress
{
    /** The stored form of an email address: trimmed and lowercase, so uniqueness and lookups never depend on case. */
    public static function normalize(string $email): string
    {
        return Str::lower(mb_trim($email));
    }
}

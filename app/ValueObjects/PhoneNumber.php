<?php

declare(strict_types=1);

namespace App\ValueObjects;

use InvalidArgumentException;

readonly class PhoneNumber
{
    private string $digits;

    public function __construct(string $value)
    {
        $digits = preg_replace('/\D+/', '', $value);

        if ($digits === null || mb_strlen($digits) !== 10) {
            throw new InvalidArgumentException('Phone number must contain exactly 10 digits.');
        }

        $this->digits = $digits;
    }

    public function __toString(): string
    {
        return $this->digits;
    }

    public function formatted(): string
    {
        return sprintf(
            '(%s) %s-%s',
            mb_substr($this->digits, 0, 3),
            mb_substr($this->digits, 3, 3),
            mb_substr($this->digits, 6, 4),
        );
    }

    public function toDigits(): string
    {
        return $this->digits;
    }
}

<?php

declare(strict_types=1);

namespace App\ValueObjects;

use App\Casts\PhoneNumberCast;
use Illuminate\Contracts\Database\Eloquent\Castable;
use InvalidArgumentException;

readonly class PhoneNumber implements Castable
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

    /**
     * @param  array<string, mixed>  $arguments
     * @return class-string<PhoneNumberCast>
     */
    public static function castUsing(array $arguments): string
    {
        return PhoneNumberCast::class;
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

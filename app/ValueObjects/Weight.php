<?php

declare(strict_types=1);

namespace App\ValueObjects;

use App\Casts\WeightCast;
use Illuminate\Contracts\Database\Eloquent\Castable;
use InvalidArgumentException;

readonly class Weight implements Castable
{
    public function __construct(public int $pounds)
    {
        if ($pounds <= 0) {
            throw new InvalidArgumentException('Weight must be greater than zero.');
        }
    }

    public function __toString(): string
    {
        return "{$this->pounds} lbs";
    }

    /**
     * @param  array<string, mixed>  $arguments
     * @return class-string<WeightCast>
     */
    public static function castUsing(array $arguments): string
    {
        return WeightCast::class;
    }

    public function toPounds(): int
    {
        return $this->pounds;
    }
}

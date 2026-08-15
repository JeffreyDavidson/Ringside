<?php

declare(strict_types=1);

namespace App\Casts;

use App\ValueObjects\Weight;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;

/** @implements CastsAttributes<Weight, Weight|int> */
class WeightCast implements CastsAttributes
{
    public function get(Model $model, string $key, mixed $value, array $attributes): Weight
    {
        return new Weight((int) $value);
    }

    public function set(Model $model, string $key, mixed $value, array $attributes): int
    {
        return $value instanceof Weight
            ? $value->toPounds()
            : (new Weight((int) $value))->toPounds();
    }
}

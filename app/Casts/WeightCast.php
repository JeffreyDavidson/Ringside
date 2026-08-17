<?php

declare(strict_types=1);

namespace App\Casts;

use App\ValueObjects\Weight;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;

/** @implements CastsAttributes<Weight, Weight|int> */
class WeightCast implements CastsAttributes
{
    public function get(Model $model, string $key, mixed $value, array $attributes): Weight
    {
        return new Weight(Arr::integer(['value' => $value], 'value'));
    }

    public function set(Model $model, string $key, mixed $value, array $attributes): int
    {
        if ($value instanceof Weight) {
            return $value->toPounds();
        }

        return (new Weight(Arr::integer(['value' => $value], 'value')))->toPounds();
    }
}

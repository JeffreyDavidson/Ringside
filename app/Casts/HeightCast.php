<?php

declare(strict_types=1);

namespace App\Casts;

use App\ValueObjects\Height;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;

/**
 * @implements CastsAttributes<Height, Height|int>
 */
class HeightCast implements CastsAttributes
{
    public function get(Model $model, string $key, mixed $value, array $attributes): Height
    {
        return Height::fromInches(Arr::integer(['value' => $value], 'value'));
    }

    public function set(Model $model, string $key, mixed $value, array $attributes): int
    {
        if ($value instanceof Height) {
            return $value->toInches();
        }

        return Height::fromInches(Arr::integer(['value' => $value], 'value'))->toInches();
    }
}

<?php

declare(strict_types=1);

namespace App\Casts;

use App\ValueObjects\PhoneNumber;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Contracts\Database\Eloquent\SerializesCastableAttributes;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;

/**
 * @implements CastsAttributes<PhoneNumber, PhoneNumber|string>
 */
class PhoneNumberCast implements CastsAttributes, SerializesCastableAttributes
{
    public function get(Model $model, string $key, mixed $value, array $attributes): ?PhoneNumber
    {
        if ($value === null) {
            return null;
        }

        return new PhoneNumber(Arr::string(['value' => $value], 'value'));
    }

    public function set(Model $model, string $key, mixed $value, array $attributes): ?string
    {
        if ($value === null) {
            return null;
        }

        if ($value instanceof PhoneNumber) {
            return $value->toDigits();
        }

        return (new PhoneNumber($value))->toDigits();
    }

    public function serialize(Model $model, string $key, mixed $value, array $attributes): ?string
    {
        if ($value === null) {
            return null;
        }

        if ($value instanceof PhoneNumber) {
            return $value->toDigits();
        }

        return Arr::string(['value' => $value], 'value');
    }
}

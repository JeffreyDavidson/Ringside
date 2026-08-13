<?php

declare(strict_types=1);

namespace App\Casts;

use App\ValueObjects\PhoneNumber;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Contracts\Database\Eloquent\SerializesCastableAttributes;
use Illuminate\Database\Eloquent\Model;

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

        return new PhoneNumber((string) $value);
    }

    public function set(Model $model, string $key, mixed $value, array $attributes): ?string
    {
        if ($value === null) {
            return null;
        }

        return $value instanceof PhoneNumber
            ? $value->toDigits()
            : (new PhoneNumber((string) $value))->toDigits();
    }

    public function serialize(Model $model, string $key, mixed $value, array $attributes): ?string
    {
        if ($value === null) {
            return null;
        }

        return $value instanceof PhoneNumber ? $value->toDigits() : (string) $value;
    }
}

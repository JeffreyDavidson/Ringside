<?php

declare(strict_types=1);

namespace App\Casts;

use App\Enums\Shared\UnitedStatesState;
use App\ValueObjects\Address;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;

/** @implements CastsAttributes<Address, Address> */
class AddressCast implements CastsAttributes
{
    /** @param array<string, mixed> $attributes */
    public function get(Model $model, string $key, mixed $value, array $attributes): Address
    {
        return new Address(
            streetAddress: (string) $attributes['street_address'],
            city: (string) $attributes['city'],
            state: UnitedStatesState::from((string) $attributes['state']),
            zipcode: (string) $attributes['zipcode'],
        );
    }

    /**
     * @param  array<string, mixed>  $attributes
     * @return array{street_address: string, city: string, state: string, zipcode: string}
     */
    public function set(Model $model, string $key, mixed $value, array $attributes): array
    {
        if (! $value instanceof Address) {
            throw new InvalidArgumentException('The address attribute must be an Address value object.');
        }

        return $value->toAttributes();
    }
}

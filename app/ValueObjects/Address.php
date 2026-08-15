<?php

declare(strict_types=1);

namespace App\ValueObjects;

use App\Enums\Shared\UnitedStatesState;
use InvalidArgumentException;

readonly class Address
{
    public function __construct(
        public string $streetAddress,
        public string $city,
        public UnitedStatesState $state,
        public string $zipcode,
    ) {
        if (mb_trim($this->streetAddress) === '' || mb_trim($this->city) === '') {
            throw new InvalidArgumentException('Address fields cannot be empty.');
        }

        if (preg_match('/^\d{5}$/', $this->zipcode) !== 1) {
            throw new InvalidArgumentException('ZIP code must contain exactly five digits.');
        }
    }

    public function formatted(): string
    {
        return "{$this->streetAddress}, {$this->city}, {$this->state->value} {$this->zipcode}";
    }

    /** @return array{street_address: string, city: string, state: string, zipcode: string} */
    public function toAttributes(): array
    {
        return [
            'street_address' => $this->streetAddress,
            'city' => $this->city,
            'state' => $this->state->value,
            'zipcode' => $this->zipcode,
        ];
    }
}

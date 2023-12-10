<?php

declare(strict_types=1);

namespace App\Data;

use App\Http\Requests\Venues\StoreRequest;
use App\Http\Requests\Venues\UpdateRequest;

readonly class VenueData
{
    /**
     * Create a new venue data instance.
     */
    public function __construct(
        public string $name,
        public string $street_address,
        public string $city,
        public string $state,
        public string $zip,
    ) {
    }

    /**
     * Create a DTO from the store request.
     */
    public static function fromStoreRequest(StoreRequest $request): self
    {
        return new self(
            $request->input('name'),
            $request->input('street_address'),
            $request->input('city'),
            $request->input('state'),
            $request->input('zip')
        );
    }

    /**
     * Create a DTO from the update request.
     */
    public static function fromUpdateRequest(UpdateRequest $request): self
    {
        return new self(
            $request->input('name'),
            $request->input('street_address'),
            $request->input('city'),
            $request->input('state'),
            $request->input('zip')
        );
    }
}

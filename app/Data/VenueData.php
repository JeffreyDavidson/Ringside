<?php

declare(strict_types=1);

namespace App\Data;

use App\Http\Requests\Venues\StoreRequest;
use App\Http\Requests\Venues\UpdateRequest;

class VenueData
{
    public function __construct(
        public string $name,
        public string $address1,
        public ?string $address2,
        public string $city,
        public string $state,
        public string $zip,
    ) {
    }

    /**
     * Create a DTO from the store request.
     *
     * @param  \App\Http\Requests\Venues\StoreRequest $request
     * @return self
     */
    public static function fromStoreRequest(StoreRequest $request): self
    {
        return new self(
            $request->input('name'),
            $request->input('address1'),
            $request->input('address2'),
            $request->input('city'),
            $request->input('state'),
            $request->input('zip')
        );
    }

    /**
     * Create a DTO from the update request.
     *
     * @param  \App\Http\Requests\Venues\UpdateRequest $request
     * @return self
     */
    public static function fromUpdateRequest(UpdateRequest $request): self
    {
        return new self(
            $request->input('name'),
            $request->input('address1'),
            $request->input('address2'),
            $request->input('city'),
            $request->input('state'),
            $request->input('zip')
        );
    }
}

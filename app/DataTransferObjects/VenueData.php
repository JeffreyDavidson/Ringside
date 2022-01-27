<?php

namespace App\DataTransferObjects;

use App\Http\Requests\Venues\StoreRequest;
use App\Http\Requests\Venues\UpdateRequest;

class VenueData
{
    /**
     * The name of the venue.
     *
     * @var string
     */
    public string $name;

    /**
     * The first line of the address for the venue.
     *
     * @var string
     */
    public string $address1;

    /**
     * The second line of the address for the venue.
     *
     * @var string|null
     */
    public ?string $address2;

    /**
     * The city where the venue is located.
     *
     * @var string
     */
    public string $city;

    /**
     * The state where the venue is located
     *
     * @var string
     */
    public string $state;

    /**
     * The zip code where the venue is located.
     *
     * @var string
     */
    public string $zip;

    /**
     * Create a DTO from the store request.
     *
     * @param  \App\Http\Requests\Venues\StoreRequest $request
     *
     * @return self
     */
    public static function fromStoreRequest(StoreRequest $request): self
    {
        $dto = new self;

        $dto->name = $request->input('name');
        $dto->address1 = $request->input('address1');
        $dto->address2 = $request->input('address2');
        $dto->city = $request->input('city');
        $dto->state = $request->input('state');
        $dto->zip = $request->input('zip');

        return $dto;
    }

    /**
     * Create a DTO from the update request.
     *
     * @param  \App\Http\Requests\Venues\UpdateRequest $request
     *
     * @return self
     */
    public static function fromUpdateRequest(UpdateRequest $request): self
    {
        $dto = new self;

        $dto->name = $request->input('name');
        $dto->address1 = $request->input('address1');
        $dto->address2 = $request->input('address2');
        $dto->city = $request->input('city');
        $dto->state = $request->input('state');
        $dto->zip = $request->input('zip');

        return $dto;
    }
}

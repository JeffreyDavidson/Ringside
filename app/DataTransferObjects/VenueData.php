<?php

namespace App\DataTransferObjects;

use App\Http\Requests\Venues\StoreRequest;
use App\Http\Requests\Venues\UpdateRequest;

class VenueData
{
    public mixed $name;

    public mixed $address1;

    public mixed $address2;

    public mixed $city;

    public mixed $state;

    public mixed $zip;

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

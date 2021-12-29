<?php

namespace App\DataTransferObjects;

use App\Http\Requests\Managers\UpdateRequest;
use App\Http\Requests\Managers\StoreRequest;

class ManagerData
{
    public string $first_name;
    public string $last_name;
    public ?string $start_date;

    public static function fromStoreRequest(StoreRequest $request): ManagerData
    {
        $dto = new self();

        $dto->first_name = $request->input('first_name');
        $dto->last_name = $request->input('last_name');
        $dto->start_date = $request->input('started_at');

        return $dto;
    }

    public static function fromUpdateRequest(UpdateRequest $request): ManagerData
    {
        $dto = new self();

        $dto->first_name = $request->input('first_name');
        $dto->last_name = $request->input('last_name');
        $dto->start_date = $request->input('started_at');

        return $dto;
    }
}

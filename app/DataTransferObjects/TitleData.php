<?php

namespace App\DataTransferObjects;

use App\Http\Requests\Titles\UpdateRequest;
use App\Http\Requests\Titles\StoreRequest;

class TitleData
{
    public string $name;
    public ?string $activation_date;

    public static function fromStoreRequest(StoreRequest $request): TitleData
    {
        $dto = new self();

        $dto->name = $request->input('name');
        $dto->activation_date = $request->input('activated_at');

        return $dto;
    }

    public static function fromUpdateRequest(UpdateRequest $request): TitleData
    {
        $dto = new self();

        $dto->name = $request->input('name');
        $dto->activation_date = $request->input('activated_at');

        return $dto;
    }
}

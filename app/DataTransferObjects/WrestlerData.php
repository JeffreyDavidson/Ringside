<?php

namespace App\DataTransferObjects;

use App\Http\Requests\Wrestlers\UpdateRequest;
use App\Http\Requests\Wrestlers\StoreRequest;

class WrestlerData
{
    public string $name;
    public integer $height;
    public integer $weight;
    public string $hometown;
    public ?string $signature_move;
    public ?string $start_date;

    public static function fromStoreRequest(StoreRequest $request): WrestlerData
    {
        $dto = new self();

        $dto->name = $request->input('name');
        $dto->height = $request->input('height');
        $dto->weight = $request->input('weight');
        $dto->signature_move = $request->input('signature_move');
        $dto->start_date = $request->input('started_at');

        return $dto;
    }

    public static function fromUpdateRequest(UpdateRequest $request): WrestlerData
    {
        $dto = new self();

        $dto->name = $request->input('name');
        $dto->height = $request->input('height');
        $dto->weight = $request->input('weight');
        $dto->signature_move = $request->input('signature_move');
        $dto->start_date = $request->input('started_at');

        return $dto;
    }
}

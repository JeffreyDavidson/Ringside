<?php

namespace App\DataTransferObjects;

use App\Http\Requests\Wrestlers\StoreRequest;
use App\Http\Requests\Wrestlers\UpdateRequest;
use Carbon\Carbon;

class WrestlerData
{
    public mixed $name;

    public mixed $height;

    public mixed $weight;

    public mixed $hometown;

    public mixed $signature_move;

    public ?Carbon $start_date;

    public static function fromStoreRequest(StoreRequest $request): self
    {
        $dto = new self;

        $dto->name = $request->input('name');
        $dto->height = $request->input('height');
        $dto->weight = $request->input('weight');
        $dto->hometown = $request->input('hometown');
        $dto->signature_move = $request->input('signature_move');
        $dto->start_date = $request->date('started_at');

        return $dto;
    }

    public static function fromUpdateRequest(UpdateRequest $request): self
    {
        $dto = new self;

        $dto->name = $request->input('name');
        $dto->height = $request->input('height');
        $dto->weight = $request->input('weight');
        $dto->hometown = $request->input('hometown');
        $dto->signature_move = $request->input('signature_move');
        $dto->start_date = $request->date('started_at');

        return $dto;
    }
}

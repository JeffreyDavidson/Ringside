<?php

namespace App\DataTransferObjects;

use App\Http\Requests\Titles\StoreRequest;
use App\Http\Requests\Titles\UpdateRequest;
use Carbon\Carbon;

class TitleData
{
    public mixed $name = 'Example Title';

    public ?Carbon $activation_date;

    public static function fromStoreRequest(StoreRequest $request): self
    {
        $dto = new self;

        $dto->name = $request->input('name');
        $dto->activation_date = $request->date('activated_at');

        return $dto;
    }

    public static function fromUpdateRequest(UpdateRequest $request): self
    {
        $dto = new self;

        $dto->name = $request->input('name');
        $dto->activation_date = $request->date('activated_at');

        return $dto;
    }
}

<?php

namespace App\DataTransferObjects;

use App\Http\Requests\Titles\StoreRequest;
use App\Http\Requests\Titles\UpdateRequest;
use Carbon\Carbon;

class TitleData
{
    /**
     * The name of the title.
     *
     * @var string
     */
    protected string $name;

    /**
     * The date to activate the title.
     *
     * @var Carbon|null
     */
    protected ?Carbon $activation_date;

    /**
     * Create a DTO from the store request.
     *
     * @param  \App\Http\Requests\Titles\StoreRequest $request
     * @return self
     */
    public static function fromStoreRequest(StoreRequest $request): self
    {
        $dto = new self();

        $dto->name = $request->input('name');
        $dto->activation_date = $request->date('activated_at');

        return $dto;
    }

    /**
     * Create a DTO from the update request.
     *
     * @param  \App\Http\Requests\Titles\UpdateRequest $request
     * @return self
     */
    public static function fromUpdateRequest(UpdateRequest $request): self
    {
        $dto = new self();

        $dto->name = $request->input('name');
        $dto->activation_date = $request->date('activated_at');

        return $dto;
    }
}

<?php

namespace App\DataTransferObjects;

use App\Http\Requests\Wrestlers\StoreRequest;
use App\Http\Requests\Wrestlers\UpdateRequest;
use Carbon\Carbon;

class WrestlerData
{
    /**
     * The name of the wrestler.
     *
     * @var string
     */
    public string $name;

    /**
     * The height of the wrestler in inches.
     *
     * @var int
     */
    public int $height;

    /**
     * The weight of the wrestler in pounds.
     *
     * @var int
     */
    public int $weight;

    /**
     * The hometown of the wrestler.
     *
     * @var string
     */
    public string $hometown;

    /**
     * The signature move of the wrestler.
     *
     * @var string|null
     */
    public ?string $signature_move;

    /**
     * The start date of the wrestler's employment.
     *
     * @var Carbon|null
     */
    public ?Carbon $start_date;

    /**
     * Create a DTO from the update request.
     *
     * @param  \App\Http\Requests\Wrestlers\UpdateRequest $request
     *
     * @return self
     */
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

    /**
     * Create a DTO from the update request.
     *
     * @param  \App\Http\Requests\Wrestlers\UpdateRequest $request
     *
     * @return self
     */
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

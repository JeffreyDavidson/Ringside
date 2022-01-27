<?php

namespace App\DataTransferObjects;

use App\Http\Requests\Referees\StoreRequest;
use App\Http\Requests\Referees\UpdateRequest;
use Carbon\Carbon;

class RefereeData
{
    /**
     * The first name of the referee.
     *
     * @var string
     */
    public string $first_name;

    /**
     * The last name of the referee.
     *
     * @var string
     */
    public string $last_name;

    /**
     * The start date of the referee's employment.
     *
     * @var Carbon|null
     */
    public ?Carbon $start_date;

    /**
     * Create a DTO from the store request.
     *
     * @param  \App\Http\Requests\Referees\StoreRequest $request
     *
     * @return self
     */
    public static function fromStoreRequest(StoreRequest $request): self
    {
        $dto = new self;

        $dto->first_name = $request->input('first_name');
        $dto->last_name = $request->input('last_name');
        $dto->start_date = $request->date('started_at');

        return $dto;
    }

    /**
     * Create a DTO from the update request.
     *
     * @param  \App\Http\Requests\Referees\UpdateRequest $request
     *
     * @return self
     */
    public static function fromUpdateRequest(UpdateRequest $request): self
    {
        $dto = new self;

        $dto->first_name = $request->input('first_name');
        $dto->last_name = $request->input('last_name');
        $dto->start_date = $request->date('started_at');

        return $dto;
    }
}

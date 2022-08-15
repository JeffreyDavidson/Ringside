<?php

declare(strict_types=1);

namespace App\Data;

use App\Http\Requests\Referees\StoreRequest;
use App\Http\Requests\Referees\UpdateRequest;
use Illuminate\Support\Carbon;

class RefereeData
{
    /**
     * Create a new referee data instance.
     *
     * @param  string  $first_name
     * @param  string  $last_name
     * @param  \Illuminate\Support\Carbon|null  $start_date
     */
    public function __construct(
        public string $first_name,
        public string $last_name,
        public ?Carbon $start_date,
    ) {
    }

    /**
     * Create a DTO from the store request.
     *
     * @param  \App\Http\Requests\Referees\StoreRequest  $request
     * @return self
     */
    public static function fromStoreRequest(StoreRequest $request): self
    {
        return new self(
            $request->input('first_name'),
            $request->input('last_name'),
            $request->date('start_date')
        );
    }

    /**
     * Create a DTO from the update request.
     *
     * @param  \App\Http\Requests\Referees\UpdateRequest  $request
     * @return self
     */
    public static function fromUpdateRequest(UpdateRequest $request): self
    {
        return new self(
            $request->input('first_name'),
            $request->input('last_name'),
            $request->date('start_date')
        );
    }
}

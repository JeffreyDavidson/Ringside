<?php

declare(strict_types=1);

namespace App\Data;

use App\Http\Requests\Managers\StoreRequest;
use App\Http\Requests\Managers\UpdateRequest;
use Illuminate\Support\Carbon;

class ManagerData
{
    /**
     * Create a new manager data instance.
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
     * @param  \App\Http\Requests\Managers\StoreRequest  $request
     * @return self
     */
    public static function fromStoreRequest(StoreRequest $request): self
    {
        return new self(
            (string) $request->input('first_name'),
            (string) $request->input('last_name'),
            $request->date('started_at')
        );
    }

    /**
     * Create a DTO from the update request.
     *
     * @param  \App\Http\Requests\Managers\UpdateRequest  $request
     * @return self
     */
    public static function fromUpdateRequest(UpdateRequest $request): self
    {
        return new self(
            (string) $request->input('first_name'),
            (string) $request->input('last_name'),
            $request->date('started_at')
        );
    }
}

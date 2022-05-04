<?php

namespace App\Data;

use App\Http\Requests\Titles\StoreRequest;
use App\Http\Requests\Titles\UpdateRequest;
use Carbon\Carbon;
use Spatie\LaravelData\Data;

class TitleData extends Data
{
    public function __construct(
        public string $name,
        public ?Carbon $activation_date
    ) {
    }

    /**
     * Create a DTO from the store request.
     *
     * @param  \App\Http\Requests\Titles\StoreRequest $request
     * @return self
     */
    public static function fromStoreRequest(StoreRequest $request): self
    {
        return new self(
            $request->input('name'),
            $request->date('activated_at'),
        );
    }

    /**
     * Create a DTO from the update request.
     *
     * @param  \App\Http\Requests\Titles\UpdateRequest $request
     * @return self
     */
    public static function fromUpdateRequest(UpdateRequest $request): self
    {
        return new self(
            $request->input('name'),
            $request->date('activated_at'),
        );
    }
}

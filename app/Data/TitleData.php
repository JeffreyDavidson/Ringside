<?php

declare(strict_types=1);

namespace App\Data;

use App\Http\Requests\Titles\StoreRequest;
use App\Http\Requests\Titles\UpdateRequest;
use Illuminate\Support\Carbon;

class TitleData
{
    /**
     * Create a new title data instance.
    public function __construct(
        public string $name,
        public ?Carbon $activation_date
    ) {
    }

    public static function fromStoreRequest(StoreRequest $request): self
    {
        return new self(
            $request->input('name'),
            $request->date('activation_date'),
        );
    }

    /**
     * Create a DTO from the update request.
     *
     * @param  \App\Http\Requests\Titles\UpdateRequest  $request
     * @return self
     */
    public static function fromUpdateRequest(UpdateRequest $request): self
    {
        return new self(
            $request->input('name'),
            $request->date('activation_date'),
        );
    }
}

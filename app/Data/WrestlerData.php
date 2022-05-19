<?php

declare(strict_types=1);

namespace App\Data;

use App\Http\Requests\Wrestlers\StoreRequest;
use App\Http\Requests\Wrestlers\UpdateRequest;
use Illuminate\Support\Carbon;

class WrestlerData
{
    public function __construct(
        public string $name,
        public ?int $height,
        public ?int $weight,
        public ?string $hometown,
        public ?string $signature_move,
        public ?Carbon $start_date,
    ) {
    }

    /**
     * Create a DTO from the update request.
     *
     * @param  \App\Http\Requests\Wrestlers\UpdateRequest $request
     * @return self
     */
    public static function fromStoreRequest(StoreRequest $request): self
    {
        return new self(
            $request->input('name'),
            ($request->input('feet') * 12) + $request->input('inches'),
            $request->input('weight'),
            $request->input('hometown'),
            $request->input('signature_move'),
            $request->date('started_at')
        );
    }

    /**
     * Create a DTO from the update request.
     *
     * @param  \App\Http\Requests\Wrestlers\UpdateRequest $request
     * @return self
     */
    public static function fromUpdateRequest(UpdateRequest $request): self
    {
        return new self(
            $request->input('name'),
            ($request->input('feet') * 12) + $request->input('inches'),
            $request->input('weight'),
            $request->input('hometown'),
            $request->input('signature_move'),
            $request->date('started_at')
        );
    }
}

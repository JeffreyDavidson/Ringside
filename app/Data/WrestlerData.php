<?php

declare(strict_types=1);

namespace App\Data;

use App\Http\Requests\Wrestlers\StoreRequest;
use App\Http\Requests\Wrestlers\UpdateRequest;
use Illuminate\Support\Carbon;

class WrestlerData
{
    /**
     * Create a new wrestler data instance.
     */
    public function __construct(
        public string $name,
        public int $height,
        public int $weight,
        public string $hometown,
        public ?string $signature_move,
        public ?Carbon $start_date,
    ) {
    }

    /**
     * Create a DTO from the update request.
     */
    public static function fromStoreRequest(StoreRequest $request): self
    {
        return new self(
            $request->input('name'),
            ($request->integer('feet') * 12) + $request->integer('inches'),
            $request->integer('weight'),
            $request->input('hometown'),
            $request->input('signature_move'),
            $request->date('start_date')
        );
    }

    /**
     * Create a DTO from the update request.
     */
    public static function fromUpdateRequest(UpdateRequest $request): self
    {
        return new self(
            $request->input('name'),
            ($request->integer('feet') * 12) + $request->integer('inches'),
            $request->integer('weight'),
            $request->input('hometown'),
            $request->input('signature_move'),
            $request->date('start_date')
        );
    }
}

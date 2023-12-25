<?php

declare(strict_types=1);

namespace App\Data;

use App\Http\Requests\Wrestlers\StoreRequest;
use App\Http\Requests\Wrestlers\UpdateRequest;
use App\ValueObjects\Height;
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
        $height = new Height($request->integer('feet'), $request->integer('inches'));

        return new self(
            $request->string('name')->value(),
            $height->toInches(),
            $request->integer('weight'),
            $request->string('hometown')->value(),
            $request->string('signature_move')->value(),
            $request->date('start_date')
        );
    }

    /**
     * Create a DTO from the update request.
     */
    public static function fromUpdateRequest(UpdateRequest $request): self
    {
        $height = new Height($request->integer('feet'), $request->integer('inches'));

        return new self(
            $request->string('name')->value(),
            $height->toInches(),
            $request->integer('weight'),
            $request->string('hometown')->value(),
            $request->string('signature_move')->value(),
            $request->date('start_date')
        );
    }
}
